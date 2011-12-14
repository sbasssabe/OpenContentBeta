<?php
/**
 * File containing the eZIdmlSpread class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlSpread extends eZIdmlBase
{
    public $attributes = array( 'pages' => array(), 'items' => array() );

    function __construct( $name = null )
    {
        if ( isset( $name ) )
            $this->attributes['name'] = $name;
    }

    public function toXML( $dom )
    {
        if ( !is_a( $dom, 'DOMDocument') )
            return false;
        
        $spreadNode = $dom->createElement( 'spread' );
        foreach ( $this->attributes as $attrName => $attrValue )
        {
            if ( is_array( $attrValue ) )
                $attrValue = serialize( $attrValue );
            
            switch ( $attrName )
            {

                case 'action':
                    $spreadNode->setAttribute( 'action', $attrValue );
                    break;
                
                case 'id':
                    $spreadNode->setAttribute( 'id', $attrValue );
                    break;

                case 'pages':
                    foreach ( $this->attributes['pages'] as $page )
                    {
                        $pageNode = $page->toXML( $dom );
                        $spreadNode->appendChild( $pageNode );
                    }
                    break;
                
                case 'items':
                    foreach ( $this->attributes['items'] as $item )
                    {
                        $itemNode = $item->toXML( $dom );
                        $spreadNode->appendChild( $itemNode );
                    }
                    break;

                default:
                    $node = $dom->createElement( $attrName );
                    $nodeValue = $dom->createTextNode( $attrValue );
                    $node->appendChild( $nodeValue );
                    $spreadNode->appendChild( $node );
                    break;
            }
        }

        return $spreadNode;
    }

    public static function createFromXML( $node )
    {
        if ( !is_a( $node, 'DOMElement') )
            return false;
        
        $newObj = new eZIdmlSpread();

        if ( $node->hasAttributes() )
        {
            foreach ( $node->attributes as $attr )
            {
                $newObj->setAttribute( $attr->name, $attr->value );
            }
        }

        foreach ( $node->childNodes as $node )
        {
            if ( $node->nodeType == XML_ELEMENT_NODE && $node->nodeName == 'page' )
            {
                $pageNode = eZIdmlPage::createFromXML( $node );
                $newObj->addPage( $pageNode );
            }
            elseif ( $node->nodeType == XML_ELEMENT_NODE && $node->nodeName == 'item' )
            {
                $pageItemNode = eZIdmlPageItem::createFromXML( $node );
                $newObj->addItem( $pageItemNode );
            }
            elseif ( $node->nodeType == XML_ELEMENT_NODE )
            {
                $newObj->setAttribute( $node->nodeName, $node->nodeValue );
            }
        }

        return $newObj;
    }

    public function addPage( eZIdmlPage $page )
    {
        foreach( $this->attributes['pages'] as $p )
        {
            if ( $p->attribute( 'id' ) == $page->attribute( 'id' ) )
                return false;
        }
        $this->attributes['pages'][ $page->attribute( 'id' ) ] = $page;
        return $page;
    }

    public function addItem( eZIdmlPageItem $item )
    {
        foreach( $this->attributes['items'] as $i )
        {
            if ( $i->attribute( 'id' ) == $item->attribute( 'id' ) )
                return false;
        }
        $this->attributes['items'][ $item->attribute( 'id' ) ] = $item;
        return $item;
    }

    public function getPageCount()
    {
        return isset( $this->attributes['pages'] ) ? count( $this->attributes['pages'] ) : 0;
    }

    public function getItemCount()
    {
        return isset( $this->attributes['items'] ) ? count( $this->attributes['items'] ) : 0;
    }

    public function getPage( $index )
    {
        $block = null;

        if ( isset( $this->attributes['pages'][$index] ) )
            $block = $this->attributes['pages'][$index];

        return $block;
    }

    public function getItem( $index )
    {
        $item = null;

        if ( isset( $this->attributes['items'][$index] ) )
            $item = $this->attributes['items'][$index];

        return $item;
    }

    public function removeProcessed()
    {
        if ( $this->hasAttribute( 'action' ) )
        {
            unset( $this->attributes['action'] );
        }

        if ( $this->getPageCount() > 0 )
        {
            foreach ( $this->attributes['pages'] as $index => $page )
            {
                $page->removeProcessed();
            }
        }
        
        if ( $this->getItemCount() > 0 )
        {
            foreach ( $this->attributes['items'] as $index => $item )
            {
                $item->removeProcessed();
            }
        }

        return $this;
    }

    public function __clone()
    {

        if( $this->getPageCount() )
        {
            foreach ( $this->attributes['pages'] as $i => $page )
            {
                $this->attributes['pages'][$i] = clone $page;
            }
        }

        if( $this->getItemCount() )
        {
            foreach ( $this->attributes['items'] as $i => $item )
            {
                $this->attributes['items'][$i] = clone $item;
            }
        }

    }

}

?>
