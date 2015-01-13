<?php
/**
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 */
namespace Magento\Framework\Interception\Fixture;

class Intercepted extends InterceptedParent implements InterceptedInterface
{
    protected $_key;

    public function A($param1)
    {
        $this->_key = $param1;
        return $this;
    }

    public function B($param1, $param2)
    {
        return '<B>' . $param1 . $param2 . $this->C($param1) . '</B>';
    }

    public function C($param1)
    {
        return '<C>' . $param1 . '</C>';
    }

    public function D($param1)
    {
        return '<D>' . $this->_key . $param1 . '</D>';
    }

    final public function E($param1)
    {
        return '<E>' . $this->_key . $param1 . '</E>';
    }

    public function F($param1)
    {
        return '<F>' . $param1 . '</F>';
    }

    public function G($param1)
    {
        return '<G>' . $param1 . "</G>";
    }

    public function K($param1)
    {
        return '<K>' . $param1 . '</K>';
    }

    public function V($param1)
    {
        return '<V>' . $param1 . '</V>';
    }

    public function W($param1)
    {
        return '<W>' . $param1 . '</W>';
    }

    public function X($param1)
    {
        return '<X>' . $param1 . '</X>';
    }

    public function Y($param1)
    {
        return '<Y>' . $param1 . '</Y>';
    }

    public function Z($param1)
    {
        return '<Z>' . $param1 . '</Z>';
    }
}
