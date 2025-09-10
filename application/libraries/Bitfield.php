<?php
// GVV Gestion vol à voile
// Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
//
// File: Bitfields.php
// Bitfields support
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 *
 * @author Martin Scotta <martinscotta@gmail.com>
 */

/**
 * This class provides a simple way to manage bitfields
 */
class Bitfield implements IteratorAggregate, Serializable {
    /**
     * Where the bits are stored
     *
     * @var int
     */
    protected $_bits;

    /**
     * Creates a new Bitfield instance
     * Additionaly you can set the bits in tow formats: int or bin-string
     *
     * <code>
     * $bit = new Bitfield( 0xf0c5 ); # set as int
     * echo $bit;
     *
     * $bit = new Bitfield( '1110110101001010' ); # set as bin-string
     * echo $bit;
     * </code>
     *
     * @param int|string $mask
     */
    function __construct(/*int|string*/$mask = false) {
        if (is_string($mask)) {
            $this->fromString($mask);
        } elseif (is_int($mask)) {
            $this->_bits = $mask;
        }
    }

    /**
     *
     * @return string
     */
    function __toString(/*void*/)
    {
        return decbin($this->_bits);
    }

    // -------------------------------------------------------------------------#
    // Methods required by interfaces

    /**
     *
     * @return ArrayIterator
     */
    function getIterator(/*void*/)
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     *
     * @return string
     */
    function serialize(/*void*/)
    {
        return strval($this->_bits);
    }

    /**
     *
     * @param string $serialized
     */
    function unserialize(/*string*/$serialized) {
        $this->fromNumber($serialized);
    }

    // -------------------------------------------------------------------------#
    // Bit Operations

    /**
     * Get the value of the bit at the $offset
     *
     * @param int $offset
     * @return boolean
     */
    function get(/*int*/$offset) {
        $mask = 1 << $offset;
        return ($mask & $this->_bits) == $mask;
    }

    /**
     * Set the bit at $offset to true
     *
     * @param int $offset
     */
    function set(/*int*/$offset) {
        $this->_bits |= 1 << $offset;
    }

    /**
     * Reset the bit at $offset.
     *
     * @param int $offset
     */
    function reset(/*int*/$offset) {
        $this->_bits &= ~ (1 << $offset);
    }

    /**
     * Toggle the bit at $offset.
     * If the bit is set then reset it, and viceversa.
     *
     * @param int $offset
     */
    function toggle(/*int*/$offset) {
        $this->_bits ^= 1 << $offset;
    }

    // -------------------------------------------------------------------------#
    // Conversion - Inputs

    /**
     * Set the bits from a $number mask
     *
     * <code>
     * $b = new Bitfield();
     * $b->fromNumber( 0x9c ); # set to 1001 1100
     * echo $b->toHex();
     * </code>
     *
     * @param int|string $number
     * @param int $base
     */
    function fromNumber(/*int*/$number) {
        $this->_bits = ( int ) $number;
    }

    /**
     * Set the bits from a $string mask.
     * The $string must only contain 1's or 0's
     *
     * <code>
     * $b = new Bitfield();
     * $b->fromString( '10011100' ); # set to 0x9c
     * echo $b->toHex();
     * </code>
     *
     * @param string $string
     */
    function fromString(/*string*/$string) {
        $this->_bits = bindec($string);
    }

    /**
     * Set the bits from a $number.
     * The $number must be in $base
     *
     * <code>
     * $b = new Bitfield();
     * $b->fromBase( '753', 8 ); # set to 111 101 011
     * echo $b->toOct();
     * </code>
     *
     * @param string $number
     * @param int $base
     */
    function fromBase(/*string*/$number,/*int*/$base) {
       $tmp = base_convert($number, $base, 10);
       $this->_bits = $tmp;
       return $this->_bits;
    }

    /**
     * Set the bits from a string with an $hex number ([0-9a-fA-F]+)
     *
     * @see Bitfield::fromBase
     *
     * @param int $number
     * @param int $base
     */
    function fromHex(/*string*/$hex) {
        $this->_bits = $this->fromBase($hex, 16);
    }

    /**
     * Set the bits from a string with an $oct number ([0-7]+)
     *
     * @see Bitfield::fromBase
     *
     * @param int $number
     * @param int $base
     */
    function fromOct(/*string*/$oct) {
        $this->_bits = $this->fromBase($oct, 8);
    }

    /**
     * Set the bits from a string with an $bin number ([01]+)
     *
     * @see Bitfield::fromBase
     *
     * @param int $bin
     */
    function fromBin(/*string*/$bin) {
        $this->_bits = $this->fromBase($bin, 2);
    }

    // -------------------------------------------------------------------------#
    // Conversion - Outputs

    /**
     * Return the bits in a numeric representation
     *
     * <code>
     * $b = new Bitfield( '10010110' ); # set to 150
     * echo $b->toNumber();
     * </code>
     *
     * @return string
     */
    function toNumber(/*void*/)
    {
        return $this->_bits;
    }

    /**
     * Return the bits in a string representation, only 1's or 0's
     *
     * If $max_size is given it will pad the bits with 0's
     *
     * <code>
     * $b = new Bitfield( 150 ); # set to 10010110
     * echo $b->toString( 16 );
     * </code>
     *
     * @param int $max_size
     * @return string
     */
    function toString(/*int*/$max_size = false) {
        if (! is_int($max_size))
            return $this->__toString();

        return str_pad($this->__toString(), $max_size, 0, STR_PAD_LEFT);
    }

    /**
     * Returns the bits as a positional array.
     *
     * <code>
     * $b = new Bitfield( 0xF0 ); # set to 1111 0000
     * print_r( $b->toArray() );
     * </code>
     *
     * @return array
     */
    function toArray(/*void*/)
    {
        return str_split(strrev($this->__toString()));
    }

    /**
     * Returns the bits as a string with a numeric representation in $base
     *
     * <code>
     * $b = new Bitfield();
     * $b->fromBase( '112233', 4 );
     * echo $b->toBase( 4 );
     * </code>
     *
     * @param int $base
     * @return string
     */
    function toBase(/*int*/$base) {
        return base_convert($this->_bits, 10, $base);
    }

    /**
     * Returns the bits as a hex-string number
     *
     * @see Bitfield::toBase
     *
     * @return string
     */
    function toHex(/*void*/)
    {
        return $this->toBase(16);
    }

    /**
     * Returns the bits as a oct-string number
     *
     * @see Bitfield::toBase
     *
     * @return string
     */
    function toOct(/*void*/)
    {
        return $this->toBase(8);
    }

    /**
     * Returns the bits as a bin-string number
     *
     * @see Bitfield::toBase
     *
     * @return string
     */
    function toBin(/*void*/)
    {
        return $this->__toString();
    }
}