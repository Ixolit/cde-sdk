<?php

namespace Ixolit\CDE\Controller;


use Ixolit\CDE\Context\Page;
use Ixolit\CDE\Exceptions\ControllerSkipViewException;
use Ixolit\CDE\Form\CookieCSRFTokenProvider;
use Ixolit\CDE\Form\CookieFormProcessor;
use Ixolit\CDE\Form\CSRFTokenProvider;
use Ixolit\CDE\Form\Form;
use Ixolit\CDE\Interfaces\FormProcessorInterface;
use Ixolit\CDE\Interfaces\RequestAPI;
use Ixolit\CDE\Interfaces\ResponseAPI;
use Psr\Http\Message\UriInterface;

/**
 * Class CDEController
 *
 * @package Ixolit\CDE\Controller
 */
class CDEController {

    /** @var FormProcessorInterface */
    private $formProcessor;

    /** @var RequestAPI */
    private $requestApi;

    /** @var ResponseAPI */
    private $responseApi;

    /** @var CSRFTokenProvider */
    private $csrfTokenProvider;

    /** @var string */
    private $language;

    /**
     * CDEController constructor.
     *
     * @param FormProcessorInterface|null $formProcessor
     * @param RequestAPI|null             $requestApi
     * @param ResponseAPI|null            $responseApi
     * @param CSRFTokenProvider|null      $csrfTokenProvider
     */
    public function __construct(FormProcessorInterface $formProcessor = null,
                                RequestAPI $requestApi = null,
                                ResponseAPI $responseApi = null,
                                CSRFTokenProvider $csrfTokenProvider = null
    ) {
        $this->formProcessor = $formProcessor;
        $this->requestApi = $requestApi;
        $this->responseApi = $responseApi;
        $this->csrfTokenProvider = $csrfTokenProvider;
    }

    /**
     * @return FormProcessorInterface
     */
    protected function getFormProcessor() {
        if (!isset($this->formProcessor)) {
            //default form processor
            $this->formProcessor = new CookieFormProcessor();
        }
        return $this->formProcessor;
    }

    /**
     * @return RequestAPI
     */
    protected function getRequestApi() {
        if (!isset($this->requestApi)) {
            //default request api
            $this->requestApi = Page::requestAPI();
        }

        return $this->requestApi;
    }

    /**
     * @return ResponseAPI
     */
    protected function getResponseApi() {
        if (!isset($this->responseApi)) {
            //default response api
            $this->responseApi = Page::responseAPI();
        }

        return $this->responseApi;
    }

    /**
     * @return CSRFTokenProvider
     */
    protected function getCSRFTokenProvider() {
        if (!isset($this->csrfTokenProvider)) {
            //default csrf token provider
            $this->csrfTokenProvider = new CookieCSRFTokenProvider($this->getRequestApi(), $this->getResponseApi());
        }

        return $this->csrfTokenProvider;
    }

    /**
     * @return string
     */
    protected function getLanguage() {
        if (!isset($this->language)) {
            $this->language = $this->getRequestApi()->getLanguage();
        }

        return $this->language;
    }

    /**
     * @param Form $form
     *
     * @return bool
     */
    protected function handleFormPost(Form $form) {
        if (!$form->isFormPost($this->getRequestApi()->getRequestParameters())) {
            $this->onFormRender($form);

            return false;
        }

        $form = $this->validateForm($form);

        if (!empty($form->getValidationErrors())) {
            $this->onFormError($form);
            //redirect

            //if overwritten onFormError doesn't redirect
            return false;
        }

        return true;
    }

    /**
     * @param Form $form
     *
     * @return Form
     */
    protected function validateForm(Form $form) {
        return $form
            ->setFromRequest($this->getRequestApi()->getPSR7())
            ->validate();
    }

    /**
     * @param Form $form
     *
     * @return Form
     */
    protected function onFormRender(Form $form) {
        $this->getFormProcessor()->restore($form, $this->getRequestApi()->getPSR7());

        return $form;
    }

    /**
     * @param Form $form
     *
     * @return void
     */
    protected function onFormError(Form $form) {
        $this->getFormProcessor()->storeForm($form);

        $redirectPath = empty($form->getErrorRedirectPath())
            ? '/' . $this->getRequestApi()->getLanguage() . $this->getRequestApi()->getPagePath() : $form->getErrorRedirectPath();

        $this->redirectToPath($redirectPath, $form->getErrorRedirectParameters());
        //exit
    }

    /**
     * @param Form   $form
     * @param string $pagePath
     * @param array  $parameters
     */
    protected function cleanFormAndRedirectTo(Form $form, $pagePath, $parameters = []) {
        $this->getFormProcessor()->cleanupForm($form);

        $this->redirectToPath($pagePath, $parameters);
        //exit
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    protected function getRequestParameter($name) {
        return $this->getRequestApi()->getRequestParameter($name);
    }

    /**
     * @param string $pagePath
     * @param array  $parameters
     * @param bool   $urlEncode
     *
     * @return UriInterface
     */
    protected function getRedirectUri($pagePath, array $parameters = [], $urlEncode = true) {
        return $this->getRequestApi()->getPSR7()->getUri()
            ->withPath($pagePath)
            ->withQuery($this->getParametersString($parameters, $urlEncode));
    }

    /**
     * @param string $pagePath
     * @param array  $parameters
     *
     * @return void
     */
    protected function redirectToPath($pagePath, array $parameters = []) {
        $redirectUri = $this->getRedirectUri($pagePath, $parameters);

        $this->redirectTo($redirectUri);
    }

    /**
     * @param UriInterface|string $redirectUri
     *
     * @return void
     *
     * @throws ControllerSkipViewException
     */
    protected function redirectTo($redirectUri) {
        $this->getResponseApi()->redirectTo($redirectUri);

        throw new ControllerSkipViewException();
    }

    /**
     * @param array $parameters
     * @param bool  $urlEncode
     *
     * @return string
     */
    protected function getParametersString($parameters = [], $urlEncode = true) {
        $parameterStringArray = [];
        foreach ($parameters as $name => $value) {
            if (\is_array($value)) {
                foreach ($value as $valuePart) {
                    $parameterStringArray[] = ($urlEncode ? \urlencode($name) : $name)
                        . '[]=' . ($urlEncode ? \urlencode($valuePart) : $valuePart);
                }
            } else {
                $parameterStringArray[] = ($urlEncode ? \urlencode($name) : $name)
                    . '=' . ($urlEncode ? \urlencode($value) : $value);
            }
        }

        return \implode('&', $parameterStringArray);
    }

}