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




