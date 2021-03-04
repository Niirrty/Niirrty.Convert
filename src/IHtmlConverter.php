<?php
/**
 * @package Niirrty\Convert
 * @version 0.3.0
 * @since   03.03.2021
 * @author  Ulf Kadner (Xclirion) <ulf.kadner@xclirion.de>
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
     * @param string|array $allowedElements
     */
    public function setAllowedElements( $allowedElements = [] );

}