<?php
/**
 * @package Niirrty\Convert
 * @version 0.3.0
 * @since   03.03.2021
 * @author  Ulf Kadner (Xclirion) <ulf.kadner@xclirion.de>
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
    public function setSource( string $source, bool $fromFile = false );

    /**
     * Gets the convert result if converting was fine, otherwise the source is returned.
     *
     * @return string
     */
    public function getResult() : string;


}

