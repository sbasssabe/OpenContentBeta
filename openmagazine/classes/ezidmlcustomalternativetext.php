<?php
/**
 * File containing the eZIdmlCustomAlternativeText class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlCustomAlternativeText implements eZIdmlCustomInterface
{

    static public function setIdmlContentAttributes( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZIdmlDoc $idml )
    {
        $return['text_content'] = '';
        $return['char_length'] = '';
        $found = false;
        $parentStory = $idml->getContent( $content->attribute( 'parent_story_id' ) );
        $image = false;
        if ( $parentStory->haveChild() )
        {
            foreach ( $parentStory->attribute( 'children' ) as $child )
            {
                if ( $child->isImage() && $child->attribute( 'xmltag_priority' ) == $content->attribute( 'xmltag_priority' ) )
                {
                    if ( $child->hasAttribute( 'alternative_text' ) )
                    {
                        $return['text_content'] = $child->attribute( 'alternative_text' );
                        $return['char_length'] = (int) strlen( $child->attribute( 'alternative_text' ) );
                        $found = true;
                    }
                }
            }
        }
        
        if ( !$found )
        {
            if ( $content->hasAttribute( 'eZContentObjectTreeNodeID' ) )
            {
                $idmlContentObjectTreeNode = eZContentObjectTreeNode::fetch( $content->attribute( 'eZContentObjectTreeNodeID' ) );
                if ( $idmlContentObjectTreeNode )
                    $currentVersion = $idmlContentObjectTreeNode->object()->currentVersion();
            }
    
            if ( $idmlContentObjectTreeNode && $content->hasAttribute( 'eZContentObjectAttributeID' ) )
            {
                $idmlContentObjectAttribute = eZContentObjectAttribute::fetch( $content->attribute( 'eZContentObjectAttributeID' ), $currentVersion->attribute( 'version' ) );
                
                if ( !$idmlContentObjectAttribute )
                {
                    return $return;
                }
                
                $tmpDom = new DOMDocument;
                $tmpXpath = false;
                $imageID = false;

                if ( $attribute->hasContent() )
                {
                    if ( $tmpDom->loadXML( $idmlContentObjectAttribute->attribute( 'data_text' ) ) )
                    {
                        $tmpXpath = new DOMXPath( $tmpDom );
                    }
                }
                
                if ( $tmpXpath !== false )
                {
                    $xquery = 'descendant::embed[@size]';
                    $tmpNodeList = $tmpXpath->query( $xquery );
                    $xmlTagPriority =  (int) $content->attribute( 'xmltag_priority' ) - 1;        
                    if ( $tmpNodeList->length >= $content->attribute( 'xmltag_priority' ) )
                    {
                        $imageID = $tmpNodeList->item( $xmlTagPriority )->getAttribute( 'object_id' );
                    }
                }

                if ( $imageID )
                {
                    $object = eZContentObject::fetch( $imageID );
                    if ( $object instanceof eZContentObject )
                    {                                
                        if ( $object->attribute( 'class_identifier' ) == 'image' )
                        {
                            $dataMap = $object->dataMap();
                            foreach( $dataMap as $att )
                            {
                                if ( $att->attribute( 'data_type_string' ) == 'ezimage' )
                                {
                                    $image = explode( '|', $att->toString() );
                                    $return['text_content'] = $image[1];
                                    $return['char_length'] = (int) strlen( $image[1] );                                    
                                }
                            }
                        }
                    }
                }
            }
        }
        return $return;
    }

    
    static public function fetchTemplateAsXML( eZIdmlContent $content, eZContentObjectAttribute $attribute )
    {
        return false;
    }
    
    static public function tplSetVariable( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZTemplate $tpl )
    {

        $tpl->setVariable( "classification", false );
        if ( $content->hasAttribute( 'text_content' ) )
                $tpl->setVariable( "content", $content->attribute( 'text_content' ) );
        
        if ( !$tpl->hasVariable( 'content' ) )
            $tpl->setVariable( "content", 'no alternative text found' );
                
        return $tpl;
    }
    
    static public function getDesignPath( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZTemplate $tpl )
    {
        return "design:content/datatype/view/ezxmltags/literal.tpl";
    }

    static public function modifyNodeList( eZIdmlContent $content, eZContentObjectAttribute $attribute, DOMNodeList $nodeList, DOMDocument $dom, DOMXPath $xpath )
    {
        return;
    }

    static public function getAttributeContent( eZIdmlContent $idmlContent, eZContentClassAttribute $classAttribute, eZIdmlPackageHandler $idmlPackage )
    {
        return '';
    }

}

?>
