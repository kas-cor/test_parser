<?php

namespace Core;

use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDomInterface;
use voku\helper\SimpleHtmlDomNodeInterface;

/**
 * Class Parser
 * @package Core
 */
class Parser implements ParserInterface {

    /**
     * Вывод в виде массива
     */
    const OUTPUT_ARRAY = 0;

    /**
     * Вывод в виде текста формата CVS
     */
    const OUTPUT_FILE = 1;

    /**
     * @var string Стартовая страница
     */
    protected $startUrl;

    /**
     * @var SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface Текущий контент
     */
    protected $content;

    /**
     * @var string Протокол сайта HTTP или HTTPS
     */
    private $scheme;

    /**
     * @var string Хост сайта
     */
    private $host;

    /**
     * @var integer Вид вывода
     */
    private $output;

    /**
     * Parser constructor.
     * @param string $startUrl Стартовый Url
     * @param integer $output Вид вывода
     */
    public function __construct($startUrl, $output = self::OUTPUT_ARRAY) {
        $this->startUrl = $startUrl;
        $this->scheme = parse_url($this->startUrl, PHP_URL_SCHEME);
        $this->host = parse_url($this->startUrl, PHP_URL_HOST);
        $this->output = $output;
    }

    /**
     * @inheritDoc
     */
    public function run() {
        $this->setContent($this->startUrl);
    }

    /**
     * Получаем ссылки
     * @param string $selector Селектор
     * @return array
     */
    public function getLinks($selector) {
        $ret = [];
        foreach ($this->content->find($selector) as $a) {
            $url = $this->convertToAbsoluteUrl($a->href);
            $ret[md5($url)] = $url;
        }

        return $ret;
    }

    /**
     * Получачем текст внутри тега
     * @param string $selector Селектор
     * @return string
     */
    public function getInnerText($selector) {
        return $this->content->findOne($selector)->innertext;
    }

    /**
     * Получаем атрибут тега
     * @param string $selector Селектор
     * @param string $attr Имя атрибута
     * @return array|string|null
     */
    public function getAttr($selector, $attr) {
        return $this->content->findOne($selector)->$attr;
    }

    /**
     * Получение множества
     * @param string $selector Селектор
     * @return SimpleHtmlDomInterface[]|SimpleHtmlDomNodeInterface
     */
    public function getMulti($selector) {
        return $this->content->findMulti($selector);
    }

    /**
     * Вывод по выбранному виду
     * @param array $data Массив данных
     * @return string
     */
    public function output($data) {
        $output = '';
        if ($this->output == self::OUTPUT_ARRAY) {
            $output = $data;
        }
        if ($this->output == self::OUTPUT_FILE) {
            $lines = [];
            $lines[] = array_keys($data[0]);
            foreach ($data as $value) {
                $lines[] = array_values($value);
            }

            $fileName = md5(uniqid());
            $f = fopen(__DIR__ . '/' . $fileName, 'w');
            foreach ($lines as $line) {
                fputcsv($f, $line);
            }
            fclose($f);

            $fileContent = file_get_contents(__DIR__ . '/' . $fileName, true);
            unlink(__DIR__ . '/' . $fileName);

            $output = $fileContent;
        }

        return $output;
    }

    /**
     * Конвертация относительного Url в абсолутный
     * @param string $url Относительный Url
     * @return string Абсолутный Url
     */
    protected function convertToAbsoluteUrl($url) {
        return $this->scheme . '://' . $this->host . $url;
    }

    /**
     * Очистка текста от тегов, пробелов и спец. символов
     * @param string $text Входящий текст
     * @return string
     */
    protected function clearText($text) {
        $text = strip_tags($text);
        $text = preg_replace('/^ +| +$|( ) +/m', '$1', $text);
        $text = preg_replace('/^(?![\r\n]\s)+|(?![\r\n]\s)+$/m', '', $text);
        $text = preg_replace('/&#13;/m', '', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Установка текущего контента
     * @param string $url Url контента
     */
    protected function setContent($url) {
        $this->content = HtmlDomParser::str_get_html(file_get_contents($url));
    }

}