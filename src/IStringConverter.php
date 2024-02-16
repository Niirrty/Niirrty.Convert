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


/**
 * @package Niirrty\Convert
 */
interface IStringConverter extends IConverter
{


    /**
     * Sets the source content that should be converted.
     *
     * @param  string $source   The source content string (or file path, if $fromFile is TRUE)
     * @param  bool   $fromFile Indicates $source is a file path, to pull content from
     * @return void
     * @throws
     */
    public function setSource( string $source, bool $fromFile = false ) : void;

    /**
     * Gets the convert result if converting was fine, otherwise the source is returned.
     *
     * @return string
     */
    public function getResult() : string;


}

