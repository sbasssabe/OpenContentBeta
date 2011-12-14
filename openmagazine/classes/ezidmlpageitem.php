<?php
/**
 * File containing the eZIdmlPageItem class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlPageItem extends eZIdmlBase
{
    public $attributes = array();

    function __construct( $name = null )
    {
        if ( isset( $name ) )
            $this->attributes['name'] = $name;
    }

    public function toXML( $dom )
    {
        if ( !is_a( $dom, 'DOMDocument') )
            return false;
        
        $itemNode = $dom->createElement( 'item' );

        foreach ( $this->attributes as $attrName => $attrValue )
        {
         
            if ( is_array( $attrValue ) )
                $attrValue = serialize( $attrValue );
            
            switch ( $attrName )
            {
                case 'id':
                    $itemNode->setAttribute( 'id', $attrValue );
                    break;

                case 'action':
                    $itemNode->setAttribute( 'action', $attrValue );
                    break;

                default:
                    $node = $dom->createElement( $attrName );
                    $nodeValue = $dom->createTextNode( $attrValue );
                    $node->appendChild( $nodeValue );
                    $itemNode->appendChild( $node );
                    break;
            }
        }

        return $itemNode;
    }

    public static function createFromXML( $node )
    {
        if ( !is_a( $node, 'DOMElement') )
            return false;
        
        $newObj = new eZIdmlPageItem();

        if ( $node->hasAttributes() )
        {
            foreach ( $node->attributes as $attr )
            {
                $newObj->setAttribute( $attr->name, $attr->value );
            }
        }

        foreach ( $node->childNodes as $node )
        {
            if ( $node->nodeType == XML_ELEMENT_NODE )
                $newObj->setAttribute( $node->nodeName, $node->nodeValue );
        }

        return $newObj;
    }

    public function removeProcessed()
    {
        if ( $this->hasAttribute( 'action' ) )
        {
            unset( $this->attributes['action'] );
        }
    }

}
?>
