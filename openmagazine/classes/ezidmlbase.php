<?php
/**
 * File containing the eZIdmlBase class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlBase
{

    public static function createFromXML( $source )
    {
        return true;
    }
    
    public function toXML( $dom )
    {
        return true;
    }
    
    public function removeProcessed()
    {
        return true;
    }

    public function attributes()
    {
        return array_keys( $this->attributes );
    }

    public function hasAttribute( $name )
    {
        return in_array( $name, array_keys( $this->attributes ) );
    }

    public function setAttribute( $name, $value )
    {
        if ( empty( $value ) )
            return false;
        if ( @unserialize( $value ) )
            $value = unserialize( $value );
        $this->attributes[$name] = $value;
    }
    
    public function attribute( $name )
    {
        if ( $this->hasAttribute( $name ) )
        {
            return $this->attributes[$name];
        }
        return false;
    }

    public function removeAttribute( $name )
    {
        if ( $this->hasAttribute( $name ) )
        {
            unset( $this->attributes[$name] );
            return true;
        }
        return false;
    }

}
?>
