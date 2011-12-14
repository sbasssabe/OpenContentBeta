<?php
/**
 * File containing the eZIdmlPage class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlPage extends eZIdmlBase
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
        
        $pageNode = $dom->createElement( 'page' );

        foreach ( $this->attributes as $attrName => $attrValue )
        {
            
            if ( is_array( $attrValue ) )
                $attrValue = serialize( $attrValue );
            
            switch ( $attrName )
            {
                case 'action':
                    $pageNode->setAttribute( 'action', $attrValue );
                    break;

                case 'id':
                    $pageNode->setAttribute( 'id', $attrValue );
                    break;

                default:
                    $node = $dom->createElement( $attrName );
                    $nodeValue = $dom->createTextNode( $attrValue );
                    $node->appendChild( $nodeValue );
                    $pageNode->appendChild( $node );
                    break;
            }
        }

        return $pageNode;
    }

    public static function createFromXML( $node )
    {
        if ( !is_a( $node, 'DOMElement') )
            return false;
        
        $newObj = new eZIdmlPage();

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

        return $this;
    }

}

?>
