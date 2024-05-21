<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class Form
{
    private const CSRF_CONTROL_NAME = '__csrf_token';
    private ?string $csrfToken = null;

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

    public function addControl(FormControlInterface $control, string $columnName): static
    {
        $this->controls[$columnName] = $control;

        return $this;
    }

    public function getControls(): array
    {
        return $this->controls;
    }

    public function getVisibleControls(): array
    {
        return array_filter($this->controls, static fn(FormControlInterface $control) => !$control instanceof HiddenInput);
    }

    public function getHiddenControls(): array
    {
        return array_filter($this->controls, static fn(FormControlInterface $control) => $control instanceof HiddenInput);
    }

    public function getData(): array
    {
        $result = [];
        foreach ($this->controls as $columnName => $control) {
            if ($columnName !== self::CSRF_CONTROL_NAME && $control->getValidationErrors() === []) {
                $result[$columnName] = $control->getValue();
            }
        }

        return $result;
    }

    public function submit(Request $request): void
    {
        $method = $request->getMethod();
        if ($method === Request::METHOD_POST) {
            $inputBag = $request->request;
        } elseif ($method === Request::METHOD_GET) {
            $inputBag = $request->query;
        } else {
            throw new \LogicException(sprintf('Method "%s" is not implemented.', $method));
        }

        $csrfCheckPassed = false;
        foreach ($this->controls as $columnName => $control) {
            if ($columnName === self::CSRF_CONTROL_NAME) {
                $csrfCheckPassed = $inputBag->get($columnName) === $this->csrfToken;
                continue;
            }

            if ($inputBag->has($columnName)) {
                // TODO: check interface
                try {
                    if ($control instanceof MultiSelect) {
                        $control->setPostValue($inputBag->all($columnName));
                    } else {
                        $control->setPostValue($inputBag->get($columnName));
                    }
                } catch (BadRequestException $e) {
                    // Ignore values that does not match the setter input type (string vs array)
                }
            }
            $control->validate($this->translator);
        }

        if (!$csrfCheckPassed && $this->csrfToken !== null) {
            $this->formErrors[] = $this->translator->trans('Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.');
        }
    }

    public function fillFromNormalizedData(array $data): void
    {
        foreach ($this->controls as $columnName => $control) {
            if ($columnName === self::CSRF_CONTROL_NAME) {
                continue;
            }
            $control->setValue($data['field_' . $columnName]);
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

    public function getGlobalFormErrors(): array
    {
        return $this->formErrors;
    }

    public function setCsrfToken(string $csrfToken): void
    {
        $this->csrfToken = $csrfToken;
        $this->addControl(
            (new HiddenInput(self::CSRF_CONTROL_NAME))->setValue($this->csrfToken),
            self::CSRF_CONTROL_NAME
        );
    }
}
