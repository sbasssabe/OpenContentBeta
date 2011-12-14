<?php
/**
 * File containing the eZIdmlCustomInterface interface.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

interface eZIdmlCustomInterface
{
    //utilizzato in eZIdmlPackageHandler::importEzContents e in eZIdmlPackageHandler::importChildEzContents
    //return array( name => value, name => value , ... )
    //set idml content new attribute
    static public function setIdmlContentAttributes( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZIdmlDoc $idml );
    
    // utilizzato in eZIdmlExporter::processAttribute e in eZIdmlExporter::addTplData
    // return boolean
    // true - fetch template as xml
    // false - fecth template as string
    static public function fetchTemplateAsXML( eZIdmlContent $content, eZContentObjectAttribute $attribute );

    // utilizzato in eZIdmlExporter::addTplData
    // set variables in referenced object $tpl 
    static public function tplSetVariable( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZTemplate $tpl );

    // utilizzato in eZIdmlExporter::addTplData
    // return string
    // template design path example: "design:content/datatype/view/ezxmltags/caption.tpl"
    static public function getDesignPath( eZIdmlContent $content, eZContentObjectAttribute $attribute, eZTemplate $tpl );
    
    // utilizzato in eZIdmlExporter::addTplData
    // return a DOMNodeList object
    // modify the nodelist fetched 
    static public function modifyNodeList( eZIdmlContent $content, eZContentObjectAttribute $attribute, DOMNodeList $nodeList, DOMDocument $dom, DOMXPath $xpath );
    
    // utilizzato in eZIdmlImporter::getAttributeContent
    static public function getAttributeContent( eZIdmlContent $idmlContent, eZContentClassAttribute $classAttribute, eZIdmlPackageHandler $idmlPackage );
}

?>
