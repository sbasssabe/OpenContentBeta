<?php
/**
 * File containing the eZIdmlCustomEzXmlText class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */


class eZIdmlCustomEzXmlText implements eZIdmlCustomInterface
{

    static public function setIdmlContentAttributes( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZIdmlDoc $idml )
    {
        return array();
    }

    
    static public function fetchTemplateAsXML( eZIdmlContent $content, eZContentObjectAttribute $attribute )
    {
        return true;
    }
    
    static public function tplSetVariable( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZTemplate $tpl )
    {

        $tpl->setVariable( "attribute", $attribute );
        return $tpl;
    }
    
    static public function getDesignPath( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZTemplate $tpl )
    {
        return  "design:content/datatype/view/idml/" . $attribute->attribute( 'data_type_string' ) . ".tpl";;
    }


    static public function modifyNodeList( eZIdmlContent $content, eZContentObjectAttribute $attribute, DOMNodeList $nodeList, DOMDocument $dom, DOMXPath $xpath )
    {
        $XMLData = $attribute->content()->XMLData;
        
        
        if ( $content->haveChild() )
        {
            $tmpDom = new DOMDocument;
            $tmpDom->loadXML( $XMLData );
            $tmpXpath = new DOMXPath( $tmpDom );
            
            foreach( $content->attribute( 'children' ) as $child )
            {
                if ( $child->hasAttribute( 'xmltag' ) && !$child->isImage() )
                {
                    $contentIni = eZINI::instance( 'content.ini' );
                    if ( in_array( $child->attribute( 'xmltag' ), $contentIni->variable( 'CustomTagSettings', 'AvailableCustomTags' ) ) )
                    {

                        $eZTagName = 'custom[@name="' . $child->attribute( 'xmltag' ) . '"]';
                        $tmpNodeList = $tmpXpath->query( 'descendant::' . $eZTagName );
                        $xmlTagPriority =  (int) $child->attribute( 'xmltag_priority' ) - 1;    
    
                        if ( $tmpNodeList->length )
                        {
                            if ( $tmpNodeList->length >= $child->attribute( 'xmltag_priority' ) )
                            {
                                $content = $tmpNodeList->item( $xmlTagPriority );
                                $content->parentNode->removeChild( $content );
                            }
                        }

                    }
                }
            }
        
            $XMLData = $tmpDom->saveXML();
        
        }
        
        $attribute->content()->XMLOutputHandler = new eZIdmlXMLOutput( $XMLData, false, $attribute->content()->ContentObjectAttribute );
        $output = $attribute->content()->attribute('output')->outputText();
        
        //eZDebug::writeNotice( $output, __METHOD__ );
        
        $newDom = new DOMDocument;
        if ( $newDom->loadXML( '<element>' . $output . '</element>') )
        {
            $newXpath = new DOMXPath( $newDom );
            $newNodeList = $newXpath->query(  '/element/*' );
            return $newNodeList;
        }
        else
        {
            eZDebug::writeError( $output, __METHOD__ );
            return $nodeList;
        }
    }

    static public function getAttributeContent( eZIdmlContent $idmlContent, eZContentClassAttribute $classAttribute, eZIdmlPackageHandler $idmlPackage )
    {
        return false;
    }

}

?>
