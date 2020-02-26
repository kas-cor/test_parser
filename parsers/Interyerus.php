<?php

namespace Parsers;

use Core\Parser;

/**
 * Class Interyerus
 * @package Parsers
 */
class Interyerus extends Parser {

    /**
     * @inheritDoc
     * @return string
     */
    public function run() {
        parent::run();

        // Получаем ссылки на все страницы с товарами
        $pagesLinks = $this->getLinks(".pager .page a");

        // Получаем ссылки на все товары на всех страницах
        $goodsLinks = [];
        foreach ($pagesLinks as $pageLink) {
            $this->setContent($pageLink);
            $links = $this->getLinks(".product a.product--name");
            $goodsLinks = array_merge($goodsLinks, $links);
        }

        // Получаем данные по каждому товару
        $allProps = [];
        $ret = [];
        foreach ($goodsLinks as $goodLink) {
            $this->setContent($goodLink);

            // Имя в теге H1
            $name = $this->getInnerText(".card-heading h1.s20");

            // Цена в артибуте data-price
            $price = $this->getAttr(".card--price .card--current-price .dynamic-price", "data-price");

            // Изображение в атрибуте src
            $imageUrl = $this->convertToAbsoluteUrl($this->getAttr(".card--image img", "src"));

            // Получаем все характеристики
            $props = [];
            $properties = $this->getMulti(".card--params p");
            foreach ($properties as $prop) {
                list($key, $value) = explode(":", $prop->innertext);
                $props[$this->clearText($key)] = $this->clearText($value);
                $allProps[$this->clearText($key)] = true;
            }

            $ret[] = [
                "name" => $name,
                "price" => $price,
                "url" => $goodLink,
                "imageUrl" => $imageUrl,
                "props" => $props,
            ];
        }

        // Переносим характиристики на один уровень
        // и учитываем что характеристири у других товаров могут отсутствовать
        array_walk($ret, function(&$item) use ($allProps) {
            $props = [];
            foreach ($allProps as $key => $tmp) {
                $props[$key] = $item['props'][$key];
            }
            unset($item['props']);
            $item = array_merge($item, $props);
        });

        return $this->output($ret);
    }

}