<?php
/**
 * File containing the eZIdmlContent class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */


class eZIdmlContent extends eZIdmlBase
{
    public $attributes = array();
    private $childIntegerIdentifier;
    private $namelessChildren = array();
    const PARAGRAPH_SEPARATOR = ' ';
    const NAMELESSCHILD_PREFIX = 'ChildOf';


    function __construct()
    {
        $this->childIntegerIdentifier = 1;
    }

    public function toXML( $dom )
    {
        if ( !is_a( $dom, 'DOMDocument') )
            return false;
        
        $itemNode = $dom->createElement( 'content' );

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
        
        $newObj = new eZIdmlContent();

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

    public function attribute( $name )
    {
        if ( $this->hasAttribute( $name ) )
        {
            return $this->attributes[$name];
        }
        return false;
    }
    
    public static function createPath( $pathArray = false, $separator = false )
    {
        if ( !$separator )
            $separator = eZSys::fileSeparator();
        
        $path = '';
        
        if ( !empty( $pathArray ) )
        {
            $path = implode( $separator, $pathArray ) .  $separator;
        }
        
        return $path;
    }
    
    public function getImageLocalName()
    {
        return $this->getImageLocalPath( array(), false, true );
    }
    
    public function getImageLocalPath( $pathArray = array(), $separator = false, $filename = false )
    {
        //nome(20caratteri)_idoggetto.ext
        
        if (  !$this->hasAttribute( 'href' ) )
        {
            return $this->attribute( 'original_href' );
        }
        else
        {
            $name = basename( $this->getImagePath() );
        }
        
        $second = '-';
        
        $imageObject = eZImageFile::fetchByFilepath( false, $this->getImagePath( true ) );
        if ( $imageObject )
        {
            $second .= $imageObject->attribute( 'id' );
        }
        else
            $second .= 0;
        
        $splitName = explode( '.', $name );
        $first = substr( $splitName[0], 0, 20 );
        $third = $splitName[1];
        
        $name = $first . $second . '.' . $third;
        
        if ( $filename )
            return $name;
        
        $idmlINI = eZINI::instance( 'openmagazine.ini' );        

        $path = eZIdmlContent::createPath( $pathArray, $separator );
        
        if ( $idmlINI->hasVariable( 'ExportImagesSettings', 'LocalImagePath' ) )
            return 'file:' . $idmlINI->variable( 'ExportImagesSettings', 'LocalImagePath' ) . $path . $name;
        else
            return 'file:/' . $path . $name;
    }
    
    public function getImageImportPath()
    {
        $idmlINI = eZINI::instance( 'idml.ini' );        
        
        if ( $this->hasAttribute( 'original_href' ) && $idmlINI->hasVariable( 'ImageImport', 'ImportImagePath' ) )
        {
            $name = basename( $this->attribute( 'original_href' ) );        
            return $idmlINI->variable( 'ImageImport', 'ImportImagePath' ) . rawurldecode( $name );
        }
        else
        {            
            return false;   
        }
    }
    
    // boolean $filepath
    public function getImagePath( $filepath = false )
    {
        $sys = eZSys::instance();
        if (  $this->hasAttribute( 'href' ) ) 
        {
            if ( $filepath )
                return $this->attribute( 'href' );
            else
            {
                return $sys->wwwDir() . '/' . $this->attribute( 'href' );
            }
        }
        else
        {
            $sys = eZSys::instance();
            $bases = eZTemplateDesignResource::allDesignBases();
            $triedFiles = array();
            $fileInfo = eZTemplateDesignResource::fileMatch( $bases, 'images', 'openmagazine/default.png', $triedFiles );
            $imgPath = $fileInfo['path'];
            $image = $sys->wwwDir() . '/' . $imgPath;
            if ( $filepath )
                return $imgPath;
            else
                return $image;
        }
    }
    
    public function getText( $separator, $open = false, $close = false )
    {
        if ( !$open || !$close )
            return str_replace( eZIdmlContent::PARAGRAPH_SEPARATOR, $separator, $this->attribute( 'text' ) );
        else
            return $open . str_replace( eZIdmlContent::PARAGRAPH_SEPARATOR, $close . $open, $this->attribute( 'text' ) ) . $close;
    }
    
    public function getInfoString()
    {
        $parent = '';
        $children = '';
        if ( $this->hasAttribute( 'parent_tag' ) )
        {
            $parent = $this->attribute( 'parent_tag' ). '/';
        }
        $namelessChildren =  $this->getNamelessChildren();
        foreach( $namelessChildren as $child )
        {
            $children .= ' + ' . $child->attribute( 'tag' );
        }
        if ( $this->hasTag() )
            return $parent . $this->attribute( 'tag' ) . $children;
        return '(untagged)';
    }

    public function setAttribute( $name, $value )
    {
        if ( empty( $value ) )
            return false;
        if ( @unserialize( $value ) )
            $value = unserialize( $value );
        
        if ( $name == 'tag' )
        {
            $value = eZIdmlContent::cleanTagName( $value );
            $this->getContentObjectAttributeMatchTag( $value );
        }
        
        if ( $name == 'href' )
        {
            if ( !$this->hasAttribute( 'href' ) && !$this->hasAttribute( 'original_href' ) )
                $this->attributes['original_href'] = $value;
        }
        
        $this->attributes[$name] = $value;
    }
    
    public function hasTag()
    {
        if ( $this->hasAttribute( 'tag' ) )
            return $this->attribute( 'tag' );
        return false;
    }
    
    public function getContentObjectAttributeMatchTag( $tag )
    {
        $idmlIni = eZINI::instance( 'openmagazine.ini' );
        preg_match_all("/(\d+)/", $tag, $match );

        if ( $match[0] )
        {

            $priority = $match[0][0];
            
            $tag = explode( $priority, $tag );
            $matchTag = $tag[0];
            $matchTagString = str_split( $matchTag );
            
            $classes = $idmlIni->variable( 'ContentTagMatch', 'Class' );
            $attributes = $idmlIni->variable( 'ContentTagMatch', 'Attribute' );
            $ezxmltags = $idmlIni->variable( 'ContentTagMatch', 'XMLTag' );
            $attributesHandlers = $idmlIni->variable( 'ContentTagMatch', 'AttributeHandler' );
            $xmltagsHandlers = $idmlIni->variable( 'ContentTagMatch', 'XMLTagHandler' );
            
            if ( count( $matchTagString ) == 2 )
            {
                
                if ( isset( $matchTagString[0] ) )
                {
                    if ( isset( $classes[ $matchTagString[0] ] ) )
                    {
                        $class_identifier = $classes[ $matchTagString[0] ];
                        $this->attributes[ 'class_identifier' ] = $class_identifier;
                    }
                }
    
                if ( isset( $matchTagString[1] ) )
                {
                    if ( isset( $attributes[ $matchTagString[1] ] ) )
                    {
                        $attribute_identifier = $attributes[ $matchTagString[1] ];
                        $this->attributes[ 'attribute_identifier' ] = $attribute_identifier;
                    }
                }
                
                if ( isset( $attributesHandlers[ $attribute_identifier ] ) )
                {
                    $classHandler = $attributesHandlers[ $attribute_identifier ]; 
                    if ( class_exists( $classHandler ) )
                    {
                        if ( in_array( 'eZIdmlCustomInterface', class_implements( $classHandler ) ) )
                        {
                            $this->attributes[ 'handler' ] = $classHandler;
                        }
                        else
                        {
                            eZDebug::writeError( $classHandler . " don't implements eZIdmlCustomInterface", __METHOD__ );
                        }
    
                    }
                }
                
                $this->attributes[ 'priority' ] = $priority;
                
            }
            elseif ( count( $matchTagString ) == 1 )
            {
                if ( isset( $matchTagString[0] ) )
                {
                    if ( isset( $ezxmltags[ $matchTagString[0] ] ) )
                    {
                        $xmlTag = $ezxmltags[ $matchTagString[0] ];
                        $this->attributes[ 'xmltag' ] = $xmlTag;
                    }
    
                    if ( isset( $xmltagsHandlers[ $xmlTag ] ) )
                    {
                        $classHandler = $xmltagsHandlers[ $xmlTag ]; 
                        if ( class_exists( $classHandler ) )
                        {
                            if ( in_array( 'eZIdmlCustomInterface', class_implements( $classHandler ) ) )
                            {
                                $this->attributes[ 'handler' ] = $classHandler;
                            }
                            else
                            {
                                eZDebug::writeError( $classHandler . " don't implements eZIdmlCustomInterface", __METHOD__ );
                            }
    
                        }
                    }
                    
                    $this->attributes[ 'xmltag_priority' ] = $priority;
                }
            }
        
        }

    }
    
    public static function cleanTagName( $tag )
    {
        $value = explode( '/', $tag );
        if ( isset( $value[1] ) )
            $value = $value[1];
        else
            $value = $value[0];
        return $value;
    }
    
    public function addStoryChild( $StoryID, $tag, eZIdmlDoc &$idml, $params = array() )
    {
        $isNameless = false;
        if ( empty( $StoryID ) )
        {
            $StoryID = eZIdmlContent::NAMELESSCHILD_PREFIX . '-' . $this->childIntegerIdentifier . '-' . $this->attribute( 'id' );
            $this->childIntegerIdentifier++;
            $isNameless = true;
        }
        
        if ( !isset( $this->attributes['children'] ) )
            $this->attributes['children'] = array();
        
        $value = eZIdmlContent::cleanTagName( $tag );
        
        $this->setAttribute( 'have_child', 1 );
        if ( $childContent = $idml->getContent( $StoryID ) )
        {
            $childContent->setAttribute( 'tag', $value );
            $childContent->setAttribute( 'parent_tag', $this->attribute( 'tag' ) );
            $childContent->setAttribute( 'parent_story_id', $this->attribute( 'id' ) );
        }
        else
        {
            $childContent = new eZIdmlContent();
            $childContent->setAttribute( 'id', $StoryID );
            $childContent->setAttribute( 'tag', $value );
            $childContent->setAttribute( 'parent_tag', $this->attribute( 'tag' ) );
            $childContent->setAttribute( 'parent_story_id', $this->attribute( 'id' ) );
            $idml->addContent( $childContent );
        }
        
        if ( !empty( $params ) )
        {
            foreach( $params as $id => $value )
            {
                $childContent->setAttribute( $id, $value );
            }
        }
        
        if ( $isNameless )
            $this->namelessChildren[] = $childContent;
        $this->attributes['children'][ $childContent->attribute( 'id' ) ] = $childContent->attribute( 'id' );
    }
    
    public function isImage()
    {
        if ( $this->hasAttribute( 'type' ) )
            return ( 'image' == $this->attribute( 'type' ) );
        return false;
    }
    
    public function haveChild( $id = false )
    {
        if ( !$id )
            return  $this->hasAttribute( 'have_child' );
        else
            return ( isset( $this->attributes['children'][$id] ) );
    }
    
    function getChild( $id )
    {
        if ( $this->haveChild( $id ) )
            return $this->attributes['children'][$id];
        return false;
    }
    
    public function getNamelessChildren()
    {

        return $this->namelessChildren;
    }
    
    public function purgeEzContents()
    {
        $this->removeAttribute( 'eZContentObjectTreeNodeID' );
        $this->removeAttribute( 'eZContentObjectAttributeID' );
        
        if ( $this->isImage() )
        {
            $this->removeAttribute( 'href' );
            $this->removeAttribute( 'alternative_text' );
        }
        
        if ( $this->hasAttribute( 'char_length' ) )
            $this->removeAttribute( 'char_length' );
    }
    
    public static function checkCharLength( $params )
    {
        $idmlIni = eZINI::instance( 'openmagazine.ini' );
        $ratioIni = $defaultCharLengthRatio = $idmlIni->variable( 'ContentTagMatch', 'DefaultCharLengthRatio' );
        $charLengthRatioArray = $idmlIni->hasVariable( 'ContentTagMatch', 'CharLengthRatio' ) ? $idmlIni->variable( 'ContentTagMatch', 'CharLengthRatio' ) : array();
        
        extract( $params );
        
        if ( $attribute_identifier && isset( $charLengthRatioArray[ $attribute_identifier ] ))
        {
            $ratioIni = $charLengthRatioArray[ $attribute_identifier ];
        }
        
        if ( $xmltag && $attribute_identifier && isset( $charLengthRatioArray[ $attribute_identifier . '/' . $xmltag ] ))
        {
            $ratioIni = $charLengthRatioArray[ $attribute_identifier . '/' . $xmltag ];
        }
        
        if ( !$original_char_length || $original_char_length == 0 )
        {
            //$original_char_length = 1;
            return false;
        }
        
        $charDiff = $original_char_length - $char_length;
        $currentRatio = abs( round ( 100 * $charDiff / $original_char_length ) );
        
        //eZDebug::writeDebug( $attribute_identifier . '/' . $xmltag . ': currentRatio ' . $currentRatio . ' > iniRatio ' . $ratioIni, __METHOD__ );
        return $currentRatio > $ratioIni;
        
    }
    
    public function getChildrenOrderedByXmltagPriority()
    {
        $children = array();
        if ( $this->haveChild() )
        {
            foreach( $this->attributes['children'] as $child )
            {
                $children[ $child->attribute( 'tag' ) ] = $child;
            }
        }
        ksort( $children );
        return $children;
    }

}
?>
