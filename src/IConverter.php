<?php
/**
 * @package Niirrty\Convert
 * @version 0.3.0
 * @since   03.03.2021
 * @author  Ulf Kadner (Xclirion) <ulf.kadner@xclirion.de>
 */


namespace Niirrty\Convert;

interface IConverter
{

    /**
     * Doing the convert and return if converting was successful.
     *
     * @return bool
     */
    public function convert() : bool;

    /**
     * Returns if the last convert call was successful.
     *
     * @return bool|null It returns null if no convert is called
     */
    public function getLastState() : ?bool;

}




