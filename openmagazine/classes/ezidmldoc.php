<?php
/**
 * File containing the eZIdmlDoc class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlDoc extends eZIdmlBase
{
    public $attributes = array();

    function __construct( $name = null )
    {
        if ( isset( $name ) )
            $this->attributes['name'] = $name;
    }
    
    public function toXML( $dom = false )
    {
        if ( !$dom )
            $dom = new DOMDocument( '1.0', 'utf-8' );
        
        $dom->formatOutput = true;
        $success = $dom->loadXML('<idml />');

        $docNode = $dom->documentElement;
        
        $this->cleanToXML();
        
        foreach ( $this->attributes as $attrName => $attrValue )
        {
                
            switch ( $attrName )
            {
                case 'id':
                    $docNode->setAttribute( 'id', $attrValue );
                    break;

                case 'action':
                    $docNode->setAttribute( 'action', $attrValue );
                    break;

                case 'spreads':
                    foreach ( $this->attributes['spreads'] as $spread )
                    {
                        $spreadNode = $spread->toXML( $dom );
                        $docNode->appendChild( $spreadNode );
                    }
                    break;

                case 'contents':
                    foreach ( $this->attributes['contents'] as $content )
                    {
                        if ( $content->haveChild() )
                        {
                            foreach( $content->attribute( 'children' ) as $c )
                            {
                                if ( is_a( $c, 'eZIdmlContent' ) )
                                {
                                    $id = $c->attribute( 'id' );
                                    $content->attributes['children'][$id] = $id;
                                }
                            }
                        }
                        $contentNode = $content->toXML( $dom );
                        $docNode->appendChild( $contentNode );
                    }
                    break;

                default:
                    if ( is_array( $attrValue ) )
                        $attrValue = serialize( $attrValue );
                    $node = $dom->createElement( $attrName );
                    $nodeValue = $dom->createTextNode( $attrValue );
                    $node->appendChild( $nodeValue );
                    $docNode->appendChild( $node );
                    break;
            }
        }

        return $dom->saveXML();
    }

    public static function createFromXML( $source )
    {
        $newObj = new eZIdmlDoc();

        if ( $source )
        {
            $dom = new DOMDocument( '1.0', 'utf-8' );
            $success = $dom->loadXML( $source );
            $root = $dom->documentElement;

            foreach ( $root->childNodes as $node )
            {
                if ( $node->nodeType == XML_ELEMENT_NODE && $node->nodeName == 'spread' )
                {
                    $spreadNode = eZIdmlSpread::createFromXML( $node );
                    $newObj->addSpread( $spreadNode );
                }
                elseif ( $node->nodeType == XML_ELEMENT_NODE && $node->nodeName == 'content' )
                {
                    $contentNode = eZIdmlContent::createFromXML( $node );
                    $newObj->addContent( $contentNode );
                }                
                elseif ( $node->nodeType == XML_ELEMENT_NODE )
                {
                    $newObj->setAttribute( $node->nodeName, $node->nodeValue );
                }
            }

            if ( $root->hasAttributes() )
            {
                foreach ( $root->attributes as $attr )
                {
                    $newObj->setAttribute( $attr->name, $attr->value );
                }
            }
        }
        eZIdmlDoc::cleanFromXML( $newObj );
        return $newObj;
    }
    
    public function cleanToXML()
    {
        $this->removeAttribute( 'content_tree' );
        $this->removeAttribute( 'idml_info' );
    }

    public static function cleanFromXML( &$newObj )
    {
        $contentTree = $newObj->contentTree();
        $newObj->setAttribute( 'content_tree', $contentTree );
        
        $pages = array();
        if ( $newObj->getSpreadCount() )
        {            
            foreach ( $newObj->attributes['spreads'] as $spread )
            {
                $pages[] = $spread->getPageCount();
            }
        }
        
        $contents = ( !empty( $contentTree ) ) ? count( $contentTree ) : 0;
        
        $idml_info = array(
            'pages' => array_sum( $pages ),
            'contents' => $contents,
            'images' => $newObj->getImageCount(),
            'classes' => $newObj->getContentClasses()
        );
        
        $newObj->setAttribute( 'idml_info', $idml_info );
    }

    public function addSpread( eZIdmlSpread $spread )
    {
        if ( !isset( $this->attributes['spreads'] ) )
            $this->attributes['spreads'] = array();
            
        foreach( $this->attributes['spreads'] as $s )
        {
            if ( $s->attribute( 'id' ) == $spread->attribute( 'id' ) )
                return false;
        }
        
        $this->attributes['spreads'][ $spread->attribute( 'id' ) ] = $spread;
        return $spread;
    }

    public function addContent( eZIdmlContent $content )
    {
        if ( !isset( $this->attributes['contents'] ) )
            $this->attributes['contents'] = array();
            
        foreach( $this->attributes['contents'] as $c )
        {
            if ( $c->attribute( 'id' ) == $content->attribute( 'id' ) )
            {
                $c->attributes = array_merge( $c->attributes, $content->attributes );
                return $c;
            }
        }
        
        $this->attributes['contents'][ $content->attribute( 'id' ) ] = $content;
        return $content;
    }

    public function getSpread( $index )
    {
        $spread = null;

        if( isset( $this->attributes['spreads'][$index] ) )
            $spread = $this->attributes['spreads'][$index];

        return $spread;
    }

    public function getSpreadCount()
    {
        return isset( $this->attributes['spreads'] ) ? count( $this->attributes['spreads'] ) : 0;
    }

    public function removeProcessed()
    {   
        if ( $this->hasAttribute( 'action' ) )
        {
            unset( $this->attributes['action'] );
        }

        if ( $this->getSpreadCount() > 0 )
        {
            foreach ( $this->attributes['spreads'] as $index => $spread )
            {
                $spread->removeProcessed();
            }
        }
    }

    public function getContent( $index )
    {
        $content = null;

        if( isset( $this->attributes['contents'][$index] ) )
            $content = $this->attributes['contents'][$index];

        return $content;
    }

    public function getImageCount()
    {
        $images = 0;
        if( !empty( $this->attributes['contents'] ) )
        {
            foreach( $this->attributes['contents'] as $c )
            {
                if ( $c->isImage() )
                {
                    $images++;
                }
            }
        }

        return $images;
    }

    public function getContentByTagName( $tag, $parentStoryId = false )
    {
        if( !empty( $this->attributes['contents'] ) )
        {
            foreach( $this->attributes['contents'] as $c )
            {
                if ( $parentStoryId )
                {
                    if ( $c->attribute( 'tag' ) == $tag  && $c->attribute( 'parent_story_id' ) == $parentStoryId )
                        return $c;
                }
                else
                {
                    if ( $c->attribute( 'tag' ) == $tag )
                        return $c;
                }
            }
        }

        return false;
    }

    public function getContentClasses()
    {
        $classes = array();
        
        if( !empty( $this->attributes['contents'] ) )
        {
            foreach( $this->attributes['contents'] as $c )
            {
                if ( $c->hasAttribute( 'class_identifier' ) )
                    $classes[] =  $c->attribute( 'class_identifier' );
            }
        }        
        return array_unique( $classes );
    }

    public function __clone()
    {
        $idmlFile = $this->attribute( 'idml_file' );
        $sourceNodeID = $this->attribute( 'source_node_id' );
        $importEzNode = $this->attribute( 'import_ez_contents' );
        $this->attributes = array();
        
        $this->setAttribute( 'action', 'clone' );
        $this->setAttribute( 'idml_file', $idmlFile );
        $this->setAttribute( 'source_node_id', $sourceNodeID );
        $this->setAttribute( 'import_ez_contents', $importEzNode );
        
        //$this->purgeEzContents();
        
        /*
         unset( $this->attributes['xml_files'] );
        if ( $this->hasAttribute('spreads') )
        {
            foreach ( $this->attributes['spreads'] as $i => $spread )
            {
                $this->attributes['spreads'][$i] = clone $spread;
            }
        }
        */
    }
    
    public function &contentTree()
    {
        $contentTree = array();
        if( !empty( $this->attributes['contents'] ) )
        {
            foreach( $this->attributes['contents'] as $content )
            {
                if ( $content->hasAttribute( 'priority' ) )
                {
                    $index = $content->attribute( 'priority' );
                    if ( $content->haveChild() )
                    {
                        foreach( $content->attribute( 'children' ) as $key => $id )
                        {
                            $content->attributes['children'][$key] = $this->getContent( $key );
                        }
                        //@TODO sort by xmltag_priority not idmlcontentname
                        ksort($content->attributes['children']);
                        $contentTree[$index][$content->attribute( 'id' )] = $content;
                    }
                    else
                    {
                        if ( !$content->hasAttribute( 'parent_story_id' ) )
                        {
                            $contentTree[$index][$content->attribute( 'id' )] = $content;
                        }
                    }
                }
            }
            ksort($contentTree);
        }
        return $contentTree;
    }
    
    public function purgeEzContents()
    {
        if ( $this->hasAttribute('contents') )
        {
            foreach ( $this->attributes['contents'] as $i => $content )
            {
                $content->purgeEzContents();
            }
        }
        $this->removeAttribute( 'have_contents' );
    }
    
    public function isValid()
    {
        if ( !$this->hasAttribute( 'xml_files' ) )
            return false;
        return true;
    }
    
}

?>
