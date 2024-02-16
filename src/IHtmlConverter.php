<?php
/**
 * @author         Ni Irrty <niirrty+code@gmail.com>
 * @copyright      © 2017-2021, Ni Irrty
 * @package        Niirrty\Convert
 * @license        MIT
 * @since          2021-03-03
 * @version        0.4.0
 */


namespace Niirrty\Convert;


interface IHtmlConverter extends IStringConverter
{

    /**
     * Sets the allowed HTML elements to pass through to the resulting content.
     *
     * Elements should be in the form "&lt;p&gt;&lt;spanp&gt;", or comma separated names "p, span" or a array
     * like array('&lt;p&gt;', '&lt;span&gt;') or array( 'p', 'span' ), with no corresponding closing tag.
     *
     * @param array|string $allowedElements
     */
    public function setAllowedElements( array|string $allowedElements = [] );

}