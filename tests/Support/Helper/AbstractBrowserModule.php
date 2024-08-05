<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Exception\ElementNotFound;
use Codeception\Module;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Crawler as SymfonyCrawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\DomCrawler\Field\TextareaFormField;
use Symfony\Component\DomCrawler\UriResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBrowserModule extends Module
{
    protected ?Response $response = null;
    protected ?Crawler $crawler = null;
    protected ?CookieJar $cookieJar;

    public function grabResponse(): string
    {
        return $this->response->getContent();
    }

    public function _initialize()
    {
        $this->cookieJar = new CookieJar();
    }

    /**
     * @throws \JsonException
     */
    public function grabJson(): ?array
    {
        $content = $this->response->getContent();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function see(string $text, ?string $selector = null): void
    {
        if ($selector !== null) {
            try {
                $content = implode("\n", $this->crawler->filter($selector)->each(function (Crawler $node) {
                    return $node->text();
                }));
            } catch (\Exception $e) {
                $this->fail('Selector "' . $selector . '" is not found. Exception: ' . $e->getMessage());
                return;
            }
        } else {
            $content = $this->response->getContent();
        }
        $condition = str_contains($content, $text) || str_contains($content, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        if (!$condition) {
            $this->debug($content);
            $this->fail('Cannot see "' . $text . '" in response');
        }
        $this->assertTrue($condition);
    }

    public function dontSee(string $text, ?string $selector = null): void
    {
        if ($selector !== null) {
            try {
                $content = implode("\n", $this->crawler->filter($selector)->each(function (Crawler $node) {
                    return $node->text();
                }));
            } catch (\Exception $e) {
                $this->fail('Selector "' . $selector . '" is not found. Exception: ' . $e->getMessage());
                return;
            }
        } else {
            $content = $this->response->getContent();
        }
        $condition = str_contains($content, $text) || str_contains($content, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        if ($condition) {
            $this->debug($content);
            $this->fail('See "' . $text . '" in response');
        }
        $this->assertFalse($condition);
    }

    public function grabMultiple(string $selector): array
    {
        return $this->crawler->filter($selector)->each(static function (Crawler $node) {
            return $node->text();
        });
    }

    public function seeElement(string $selector): void
    {
        $condition = $this->crawler->filter($selector)->count() > 0;
        if (!$condition) {
            $this->debug($this->response->getContent());
            $this->fail('Cannot see "' . $selector . '" in response');
        }
        $this->assertTrue($condition);
    }

    public function dontSeeElement(string $selector): void
    {
        $this->assertSame(0, $this->crawler->filter($selector)->count());
    }

    public function grabAttributeFrom(string $selector, string $attribute): ?string
    {
        return $this->crawler->filter($selector)->attr($attribute);
    }

    public function seeResponseCodeIs(int $code): void
    {
        $this->assertEquals($code, $this->response->getStatusCode());
    }

    public function amOnPage(string $url): void
    {
        $this->doRequest(Request::create($url));
    }

    public function grabLocation(): string
    {
        return $this->response->headers->get('Location');
    }

    public function seeLocationIs(string $location): void
    {
        $this->assertEquals($location, $this->response->headers->get('Location'));
    }

    public function seeLocationMatches(string $locationRegex): void
    {
        $this->assertMatchesRegularExpression($locationRegex, $this->response->headers->get('Location'));
    }

    public function followRedirect(): void
    {
        $location = $this->response->headers->get('Location');
        $uri = UriResolver::resolve($location, $this->crawler->getUri());
        $this->doRequest(Request::create($uri));
    }

    public function click(string $selector): void
    {
        $crawler = $this->crawler->selectLink($selector);
        if ($crawler->count() === 0) {
            $crawler = $this->crawler->filter($selector);
        }
        $this->doRequest(Request::create($crawler->link()->getUri()));
    }

    public function submitForm(string $selector, array $data): void
    {
        $formCrawler = $this->crawler->filter($selector);
        $button      = $formCrawler->filter('button[type="submit"]');
        if ($button->count() > 0 && $button->attr('name')) {
            $buttonData = [$button->attr('name') => $button->attr('value') ?? ''];
        } else {
            $buttonData = [];
        }

        $form = $formCrawler->form();
        $form->disableValidation(); // see https://stackoverflow.com/questions/57386450/how-to-tick-a-specific-checkbox-from-a-multi-dimensional-field-in-a-symfony-form
        $form->setValues($data);

        // Hack for crawler. Looks like it doesn't add button data for no reason, so we do it manually here
        $phpValues = array_merge($buttonData, $form->getPhpValues());
        $request   = Request::create($form->getUri(), $form->getMethod(), $phpValues);
        $this->doRequest($request);
    }

    public function sendPost(string $url, array $data = [], array $files = []): void
    {
        $request = Request::create($url, 'POST', $data, [], $files);
        $this->doRequest($request);
    }

    public function sendAjaxPostRequest(string $uri, array $params = []): void
    {
        $this->sendAjaxRequest('POST', $uri, $params);
    }

    public function sendAjaxRequest(string $method, string $uri, array $params = []): void
    {
        $request = Request::create($uri, $method, $params, [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->doRequest($request);
    }

    public function grabFormValues(string $selector): array
    {
        return $this->crawler->filter($selector)->form()->getValues();
    }

    public function grabValueFrom($field): mixed
    {
        $nodes = $this->crawler->filter($field);
        if ($nodes->count() === 0) {
            throw new ElementNotFound($field, 'Field');
        }

        if ($nodes->filter('textarea')->count() !== 0) {
            return (new TextareaFormField($nodes->filter('textarea')->getNode(0)))->getValue();
        }

        $input = $nodes->filter('input');
        if ($input->count() !== 0) {
            return $this->getInputValue($input);
        }

        if ($nodes->filter('select')->count() !== 0) {
            $field   = new ChoiceFormField($nodes->filter('select')->getNode(0));
            $options = $nodes->filter('option[selected]');
            $values  = [];

            foreach ($options as $option) {
                $values[] = $option->getAttribute('value');
            }

            if (!$field->isMultiple()) {
                return reset($values);
            }

            return $values;
        }

        $this->fail("Element {$nodes} is not a form field or does not contain a form field");
    }

    protected function getInputValue(SymfonyCrawler $input): array|string
    {
        $inputType = $input->attr('type');
        if ($inputType === 'checkbox' || $inputType === 'radio') {
            $values = [];

            foreach ($input->filter(':checked') as $checkbox) {
                $values[] = $checkbox->getAttribute('value');
            }

            return $values;
        }

        return (new InputFormField($input->getNode(0)))->getValue();
    }

    public function grabAndMatch(string $selector, string $regex): ?array
    {
        $html = $this->crawler->filter($selector)->outerHtml();
        if (preg_match($regex, $html, $matches)) {
            return $matches;
        }
        return null;
    }

    protected function doRequest(Request $request): void
    {
        $uri = $request->getUri();
        $request->cookies->replace($this->cookieJar->allValues($uri));
        $this->debug(sprintf("%s %s", $request->getMethod(), $uri));
        $this->response = $this->doRealRequest($request);
        $this->cookieJar->updateFromSetCookie($this->response->headers->all('Set-Cookie'), $uri);
        $content = $this->response->getContent();
        $this->debug(sprintf(
            "Get %s response:\n%s\n\n%s",
            $this->response->getStatusCode(),
            $this->response->headers,
            mb_substr($content, 0, 100)
        ));
        $this->crawler = new Crawler($content, $uri);
    }

    abstract protected function doRealRequest(Request $request): Response;
}
