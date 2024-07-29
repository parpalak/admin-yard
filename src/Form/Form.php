<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use S2\AdminYard\Helper\RandomHelper;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class Form
{
    private const CSRF_FIELD_NAME = '__csrf_token';
    private ?string $csrfToken = null;
    private bool $csrfCheckPassed = false;

    /**
     * @var string[]
     */
    private array $formErrors = [];

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @var array<string, FormControlInterface>
     */
    private array $controls = [];

    public function addControl(FormControlInterface $control, string $fieldName): static
    {
        $this->controls[$fieldName] = $control;

        return $this;
    }

    public function getControls(): array
    {
        return $this->controls;
    }

    /**
     * @return array<string, FormControlInterface>
     */
    public function getVisibleControls(): array
    {
        return array_filter($this->controls, static fn(FormControlInterface $control) => !$control instanceof HiddenInput);
    }

    /**
     * @return array<string, FormControlInterface>
     */
    public function getHiddenControls(): array
    {
        return array_filter($this->controls, static fn(FormControlInterface $control) => $control instanceof HiddenInput);
    }

    public function getData(bool $includeHidden = true): array
    {
        $result = [];
        foreach ($this->controls as $fieldName => $control) {
            if (!$includeHidden && $control instanceof HiddenInput) {
                continue;
            }
            if ($fieldName !== self::CSRF_FIELD_NAME && $control->getValidationErrors() === []) {
                $result[$fieldName] = $control->getValue();
            }
        }

        return $result;
    }

    public function submit(Request $request, bool $overwriteEmptyArrayControls = false): void
    {
        $method = $request->getRealMethod();
        if ($method === Request::METHOD_POST) {
            $inputBag = $request->request;
        } elseif ($method === Request::METHOD_GET) {
            $inputBag = $request->query;
        } else {
            throw new \LogicException(sprintf('Method "%s" is not implemented.', $method));
        }

        $this->csrfCheckPassed = false;
        foreach ($this->controls as $columnName => $control) {
            if ($columnName === self::CSRF_FIELD_NAME) {
                $this->csrfCheckPassed = $inputBag->get($columnName) === $this->csrfToken || $this->checkTempCsrfToken($request, $this->csrfToken);
                continue;
            }

            if ($inputBag->has($columnName)) {
                // TODO: check interface instead of MultiSelect
                try {
                    if ($control instanceof MultiSelect) {
                        $control->setPostValue($inputBag->all($columnName));
                    } else {
                        $control->setPostValue($inputBag->get($columnName));
                    }
                } catch (BadRequestException $e) {
                    // Ignore values that does not match the setter input type (string vs array)
                }
            } else {
                /**
                 * Browsers do not send any data in checkbox arrays when there are no checkboxes checked:
                 * <input type="checkbox" name="foo[]" value="bar" />
                 * <input type="checkbox" name="foo[]" value="qux" />
                 *
                 * So we cannot distinguish two situations:
                 * 1. There is no GET parameter foo because the user has not submitted any form.
                 * 2. There is no GET parameter foo because the user has not checked any checkboxes however the form has been submitted.
                 *
                 * The workaround is to check if the form data contains the key-value pair for the submit button.
                 * $overwriteEmptyArrayControls is used for this purpose.
                 *
                 * Other controls like input do not have this problem.
                 * There is no key (url === '/') when the form is not submitted,
                 * and there is an empty value (url === '/?text=') when the form is submitted.
                 */
                if ($overwriteEmptyArrayControls && $control instanceof MultiSelect) {
                    $control->setPostValue([]);
                }
            }
            $control->validate($this->translator);
        }

        if (!$this->csrfCheckPassed && $this->csrfToken !== null) {
            $this->formErrors[] = $this->translator->trans('Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.');
        }
    }

    public function fillFromArray(array $data, array $fieldPrefixes = ['']): void
    {
        foreach ($this->controls as $fieldName => $control) {
            if ($fieldName === self::CSRF_FIELD_NAME) {
                continue;
            }
            foreach ($fieldPrefixes as $fieldPrefix) {
                if (\array_key_exists($fieldPrefix . $fieldName, $data)) {
                    $control->setValue($data[$fieldPrefix . $fieldName]);
                    break;
                }
            }
        }
    }

    public function isValid(): bool
    {
        if ($this->formErrors !== []) {
            return false;
        }

        foreach ($this->controls as $control) {
            if ($control->getValidationErrors() !== []) {
                return false;
            }
        }

        return true;
    }

    public function isCsrfCheckPassed(): bool
    {
        return $this->csrfCheckPassed;
    }

    public function getGlobalFormErrors(): array
    {
        return $this->formErrors;
    }

    public function setCsrfToken(string $csrfToken): void
    {
        $this->csrfToken = $csrfToken;
        $this->addControl(
            (new HiddenInput(self::CSRF_FIELD_NAME))->setValue($this->csrfToken),
            self::CSRF_FIELD_NAME
        );
    }

    /**
     * @return array<string, string[]>
     */
    public function getFieldErrors(): array
    {
        return array_map(static fn(FormControlInterface $control) => $control->getValidationErrors(), $this->controls);
    }

    /**
     * @return string[]
     */
    public function getMergedErrors(): array
    {
        return array_merge($this->formErrors, array_merge(...array_values($this->getFieldErrors())));
    }

    /**
     * Generating a temporary CSRF token using Signed Double-Submit Cookie.
     * It is checked when Synchronizer Token Pattern fails for ajax requests.
     *
     * A typical scenario for this check is as follows. The page with the AJAX form was loaded by the user
     * and left for some time. During this time, the session expired, and the main token disappeared with it.
     * To avoid making the user refresh the page and lose data, we issue a temporary token through cookies,
     * and the client script that sends the data via AJAX must send this token in the X-AdminYard-CSRF-Token header.
     *
     * @see https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#signed-double-submit-cookie-recommended
     */
    public static function generateTempCsrfToken(string $realCsrfToken): string
    {
        $message = RandomHelper::getRandomHexString32() . '-' . time();

        return sha1($realCsrfToken . '-' . $message) . '-' . $message;
    }

    private function checkTempCsrfToken(Request $request, string $realCsrfToken): bool
    {
        $tempCsrfToken = $request->headers->get('X-AdminYard-CSRF-Token');
        if ($tempCsrfToken === null) {
            return false;
        }

        $parts = explode('-', $tempCsrfToken);
        if (\count($parts) !== 3) {
            return false;
        }

        [$hash, $randomStr, $time] = $parts;
        if (\strlen($randomStr) !== 32) {
            return false;
        }

        $now = time();
        if ($time <= $now - 60 || $time > $now) {
            return false;
        }

        if (sha1($realCsrfToken . '-' . $randomStr . '-' . $time) !== $hash) {
            return false;
        }

        return true;
    }
}
