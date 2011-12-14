<?php
/**
 * File containing the eZIdmlPackageHandler class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlPackageHandler
{
    public $idml;

    private $filePath;

    private $XMLfiles;

    private $SVGfiles;

    private $itemsToShape = array(
        'TextFrame' => 'polygon',
        'Rectangle' => 'polygon',
        'Oval' => 'ellipse',
        'GraphicLine' => 'line',
        'Image' => 'image',
        'Polygon' => 'polygon'
    );

    private $taggableItems = array(
        'TextFrame',
        'Image'
    );

    private $tagsColorArray = array();

    private $itemsTagArray = array();

    const LOGFILENAME = 'idml_import.log';
    
    function __construct( &$contentObjectAttribute )
    {
        $this->ContentObjectAttributeData = array();
        $this->idml = $contentObjectAttribute->content();

        if ( is_object( $contentObjectAttribute ) )
        {
            $this->ContentObject = $contentObjectAttribute->object();
            $this->ContentObjectAttributeData['id'] = $contentObjectAttribute->attribute( 'id' );
            $this->ContentObjectAttributeData['contentobject_id'] = $contentObjectAttribute->attribute( 'contentobject_id' );
            $this->ContentObjectAttributeData['version'] = $contentObjectAttribute->attribute( 'version' );
            $this->ContentObjectAttributeData['language_code'] = $contentObjectAttribute->attribute( 'language_code' );
            $this->ContentObjectAttributeData['can_translate'] = $contentObjectAttribute->attribute( 'can_translate' );
            $this->ContentObjectAttributeData['data_text'] = $contentObjectAttribute->attribute( 'data_text' );
            $this->ContentObjectAttributeData['DataTypeCustom'] = $contentObjectAttribute->DataTypeCustom;

            if ( !is_array( $this->ContentObjectAttributeData['DataTypeCustom'] ) )
            {
                $this->ContentObjectAttributeData['DataTypeCustom'] = array();
            }
        }
        else
        {
            eZDebug::writeWarning( 'Invalid eZContentObjectAttribute', 'eZIdmlPackageHandler' );
        }      
    }

    public function initializeFromHTTPFile( $httpFile )
    {
        if ( $httpFile instanceof eZHTTPFile )
        {
            $mimeData = eZMimeType::findByFileContents( $httpFile->attribute( 'filename' ) );
            
            if ( !$mimeData['is_valid'] )
            {
                $mimeData = eZMimeType::findByName( $httpFile->attribute( 'mime_type' ) );
                if ( !$mimeData['is_valid'] )
                {
                    $mimeData = eZMimeType::findByURL( $httpFile->attribute( 'original_filename' ) );
                }
            }

            $contentVersion = eZContentObjectVersion::fetchVersion( $this->ContentObjectAttributeData['version'],
                                                                    $this->ContentObjectAttributeData['contentobject_id'] );
            $objectPathString = $this->filePath( $this->ContentObjectAttributeData, $contentVersion );

            $objectName = explode( '.', $httpFile->attribute('original_filename') );
            eZMimeType::changeBaseName( $mimeData, $objectName[0] );
            eZMimeType::changeDirectoryPath( $mimeData, $objectPathString );

            $httpFile->store( false, false, $mimeData );
    
            $originalFilename = $httpFile->attribute( 'original_filename' );
            eZLog::write( __METHOD__ . ': Imported ' . $originalFilename, eZIdmlPackageHandler::LOGFILENAME );
            return $this->initialize( $mimeData, $originalFilename );
        }
    }

    public function initializeFromFile( $filename )
    {
        if ( !file_exists( $filename ) )
        {
            return false;
        }
        
        $originalFilename = basename( $filename );
        $mimeData = eZMimeType::findByFileContents( $filename );
        if ( !$mimeData['is_valid'] and
             $originalFilename != $filename )
        {
            $mimeData = eZMimeType::findByFileContents( $originalFilename );
        }

        $contentVersion = eZContentObjectVersion::fetchVersion( $this->ContentObjectAttributeData['version'],
                                                                $this->ContentObjectAttributeData['contentobject_id'] );
        $objectName = explode( '.', $originalFilename );
        $objectPathString = $this->filePath( $this->ContentObjectAttributeData, $contentVersion, true );

        eZMimeType::changeBaseName( $mimeData, $objectName[0] );
        eZMimeType::changeDirectoryPath( $mimeData, $objectPathString );
        if ( !file_exists( $mimeData['dirpath'] ) )
        {
            eZDir::mkdir( $mimeData['dirpath'], false, true );
        }

        eZFileHandler::copy( $filename, $mimeData['url'] );
        eZLog::write( __METHOD__ . ': Imported ' . $filename, eZIdmlPackageHandler::LOGFILENAME );
        return $this->initialize( $mimeData, $originalFilename );
    }
    
    public function initializeFromExistingObject()
    {
        if ( $this->idml->hasAttribute( 'idml_file' ) )
        {
            $filename = $this->idml->attribute( 'idml_file' );
            if ( !file_exists( $filename ) )
            {
                return false;
            }
    
            $originalFilename = basename( $filename );
            $mimeData = eZMimeType::findByFileContents( $filename );
            if ( !$mimeData['is_valid'] and
                 $originalFilename != $filename )
            {
                $mimeData = eZMimeType::findByFileContents( $originalFilename );
            }
    
            $contentVersion = eZContentObjectVersion::fetchVersion( $this->ContentObjectAttributeData['version'],
                                                                    $this->ContentObjectAttributeData['contentobject_id'] );
            $objectName = explode( '.', $originalFilename );
            $objectPathString = $this->filePath( $this->ContentObjectAttributeData, $contentVersion, true );
    
            eZMimeType::changeBaseName( $mimeData, $objectName[0] );
            eZMimeType::changeDirectoryPath( $mimeData, $objectPathString );
            if ( !file_exists( $mimeData['dirpath'] ) )
            {
                eZDir::mkdir( $mimeData['dirpath'], false, true );
            }
    
            eZFileHandler::copy( $filename, $mimeData['url'] );
            eZLog::write( __METHOD__ . ': Imported ' . $filename, eZIdmlPackageHandler::LOGFILENAME );
            return $this->initialize( $mimeData, $originalFilename );
        }
        return false;
    }

    private function initialize( $mimeData, $originalFilename )
    {
        $fileHandler = eZClusterFileHandler::instance();
        $filePath = $mimeData['url'];
        $fileHandler->fileStore( $filePath, 'idml', false, $mimeData['name'] );
        $this->filePath = $filePath;
        $this->idml->setAttribute( 'idml_file', $this->getFilePath() );
        eZLog::write( '==== Start initialize process for ' . $this->getFilePath()  . ' ( ContentObjectAttribute #' . $this->ContentObjectAttributeData['id'] . ') ====', eZIdmlPackageHandler::LOGFILENAME );
        $this->extract();
        return true;
    }

    static function removeFile( $filename )
    {
        $file = eZClusterFileHandler::instance( $filename );
        if ( $file->exists() )
        {
            $file->delete();
            eZDir::cleanupEmptyDirectories( $filename );
        }
    }
    
    private function removeAllFiles()
    {
        eZLog::write( 'Removing all files of ContentObjectAttribute #' . $this->ContentObjectAttributeData['id'] . ' version ' . $this->ContentObjectAttributeData['version'] , eZIdmlPackageHandler::LOGFILENAME );
        
        if ( $this->idml->hasAttribute( 'idml_file' ) )
        {
            eZIdmlPackageHandler::removeFile( $this->idml->attribute( 'idml_file' ) );
        }
        if ( $this->idml->hasAttribute( 'xml_files' ) )
        {
            $xmlFiles = $this->idml->attribute( 'xml_files' );
            foreach ( $xmlFiles as $xmlFile )
            {
                eZIdmlPackageHandler::removeFile( $xmlFile );
            } 
        }
        if ( $this->idml->hasAttribute( 'svg_files' ) )
        {
            $svgFiles = $this->idml->attribute( 'svg_files' );
            foreach ( $svgFiles as $svgFile )
            {
                eZIdmlPackageHandler::removeFile( $svgFile );
            } 
        }
    }

    public function reset( $regenerate = false )
    {
        $this->removeAllFiles();
        
        if ( !$regenerate )
            $this->removeSourceNode();
        else
        {
            $backupSourceNode = false;
            $backupImportEzContents = false;
            
            if ( $this->idml->hasAttribute( 'source_node_id' ) )
                $backupSourceNode = $this->idml->attribute( 'source_node_id' );
            if ( $this->idml->hasAttribute( 'import_ez_contents' ) )
                $backupImportEzContents = $this->idml->attribute( 'import_ez_contents' );
            
            $this->idml = new eZIdmlDoc();
            if ( $backupSourceNode )
                $this->idml->setAttribute( 'source_node_id',  $backupSourceNode );
            if ( $backupImportEzContents )
                $this->idml->setAttribute( 'import_ez_contents',  $backupImportEzContents );
        }
    }
    
    private function isValidIdmlFile( $file )
    {
        //@TODO
        return true;
    }

    private function filePath( $contentObjectAttribute, $contentVersion )
    {
        $ini = eZINI::instance( 'idml.ini' );
        $contentSubtree = $ini->variable( 'FileSettings', 'PublishedIdml' );
        $pathString = $contentSubtree;
        $attributeID = $this->ContentObjectAttributeData['id'];
        $attributeVersion = $this->ContentObjectAttributeData['version'];
        $attributeLanguage = $this->ContentObjectAttributeData['language_code'];
        $identifierString = $attributeID . eZSys::fileSeparator() . $attributeVersion . '-' . $attributeLanguage;
        $filePath = eZSys::storageDirectory() . eZSys::fileSeparator() . $pathString . eZSys::fileSeparator() . $identifierString;
        return $filePath;
    }
    
    public function getFilePath()
    {
        if ( empty( $this->filePath ) && $this->idml->hasAttribute( 'idml_file' ) )
            $this->filePath = $this->idml->attribute( 'idml_file' );
            
        return $this->filePath;
    }
    
    public function getDirPath()
    {
        return eZDir::dirpath( $this->getFilePath() );
    }

    public function addXMLFiles( $file )
    {
        $path = eZDir::dirpath( $file );
        $this->XMLfiles[ $file ] = $this->getDirPath() . eZSys::fileSeparator() . $file;
    }

    public function getXMLFiles()
    {
        if ( empty( $this->XMLfiles ) && $this->idml->hasAttribute( 'xml_files' ) )
        {
            $this->XMLfiles =  $this->idml->attribute( 'xml_files' );
        }
        return $this->XMLfiles;
    }

    public function getXMLFile( $index )
    {
        $this->getXMLFiles();
        return isset ( $this->XMLfiles[$index] ) ? $this->XMLfiles[$index] : false;
    }    

    public function getSVGFiles()
    {
        return $this->SVGfiles;
    }

    public function getSVGFile( $spreadID )
    {
        return isset ( $this->SVGfiles[$spreadID] ) ? $this->SVGfiles[$spreadID] : false;
    }
    
    private function extract()
    {    
        if ( empty( $this->XMLfiles ) && !empty( $this->filePath ) )
        {
            $archive = ezcArchive::open( $this->getFilePath() );
            while ( $archive->valid() )
            {
                $entry = $archive->current();
                $archive->extractCurrent( $this->getDirPath() );
                $this->addXMLFiles( $entry->getPath() );
                $archive->next();
            }
            $this->idml->setAttribute( 'xml_files', $this->getXMLFiles() );
            eZLog::write( 'Extracted xml files', eZIdmlPackageHandler::LOGFILENAME );

            $this->extractTagsData();
            eZLog::write( 'Extracted tag data', eZIdmlPackageHandler::LOGFILENAME );

            $this->extractStoriesData();
            eZLog::write( 'Extracted stories data', eZIdmlPackageHandler::LOGFILENAME );

            $this->extractSpreadsData();
            eZLog::write( 'Extracted spreads data', eZIdmlPackageHandler::LOGFILENAME );
            
            eZIdmlPackageHandler::importEzContents( $this->idml );
            
            $this->createSvgFiles();
            eZLog::write( 'Created svg files', eZIdmlPackageHandler::LOGFILENAME );
            
            eZLog::write( '==== End extract process for ' . $this->getFilePath()  . ' ( ContentObjectAttribute #' . $this->ContentObjectAttributeData['id'] . ') ====', eZIdmlPackageHandler::LOGFILENAME );
        }
    }
    
    public static function importEzContents( &$idml )
    {
        if ( !$idml->hasAttribute( 'import_ez_contents' ) )
            return false;
        if ( !$idml->hasAttribute( 'source_node_id' ) )
            return false;
        if ( !$idml->isValid() )
            return false;
        
        $sourceNodeID = $idml->attribute( 'source_node_id' );
        
        $sourceNode = eZContentObjectTreeNode::fetch( $sourceNodeID );
        
        $modified_time_stored = ( $idml->hasAttribute( 'modified_source_node' ) ) ? $idml->attribute( 'modified_source_node' ) : 0;

        if ( $sourceNode && ( $sourceNode->attribute( 'modified_subnode' ) > $modified_time_stored ) )
        {
            eZLog::write( 'Update IDML ' . $idml->attribute( 'idml_file' ) . ' with the corresponding attributes of the children of the node ' . $sourceNode->attribute( 'name' ) . ' #' . $sourceNode->attribute( 'node_id' ), eZIdmlPackageHandler::LOGFILENAME );
            
            $idml->setAttribute( 'modified_source_node',  $sourceNode->attribute( 'modified_subnode' ) );
            
            $classFilters = array();
            $classes = $idml->getContentClasses();
            if ( !empty( $classes ) )
            {
                $classFilters = array(
                    'ClassFilterType' => 'include',
                    'ClassFilterArray' => $classes
                );
            }  
            
            $childrenParams = array(			
                'Depth' => 1,
                'SortBy' => array( 'priority', true ),
                'AttributeFilter'  => array( array( 'priority', '>', 0 ) )
            );
            $childrenParams = array_merge( $childrenParams, $classFilters );
            $children = eZContentObjectTreeNode::subTreeByNodeID(  $childrenParams, $sourceNode->attribute( 'node_id' ) );
            $idml->purgeEzContents();
            $contentTree = $idml->contentTree();

            if ( !empty( $contentTree ) && $children )
            {
                foreach( $children as $node )
                {
                    $contents =  isset( $contentTree[ $node->attribute( 'priority' ) ] ) ? $contentTree[ $node->attribute( 'priority' ) ] : array();
                    foreach( $contents as $content )
                    {   
                        if ( $content instanceof eZIdmlContent )
                        {
                            $dataMap = $node->dataMap();
                            if ( isset( $dataMap[ $content->attribute( 'attribute_identifier' ) ] ) )
                            {
                                if ( $dataMap[ $content->attribute( 'attribute_identifier' ) ] instanceof eZContentObjectAttribute )
                                {
                                    $attribute = $dataMap[ $content->attribute( 'attribute_identifier' ) ];
                                    $content->setAttribute( 'eZContentObjectTreeNodeID', $node->attribute( 'node_id' ) );
                                    $content->setAttribute( 'eZContentObjectAttributeID', $attribute->attribute( 'id' ) );
                                    
                                    if ( $content->isImage() )
                                    {
                                        if ( $attribute->attribute( 'data_type_string' ) == 'ezimage' )
                                        {                                            
                                            $image = explode( '|', $attribute->toString() );
                                            $content->setAttribute( 'href', $image[0] );
                                            $content->setAttribute( 'alternative_text', $image[1] );
                                        }
                                    }
                                    else
                                    {
                                        $char_length = $attribute->toString();
                                        $char_length = preg_replace( '#\s+#',' ', $char_length );
                                        $content->setAttribute( 'char_length', strlen( trim( strip_tags( $char_length ) ) ) );
                                    }
                                    
                                    if ( $content->hasAttribute( 'handler' ) )
                                    {
                                        $classHandler = $content->attribute( 'handler' );
                                        $handlerAttributes = call_user_func( array( $classHandler, 'setIdmlContentAttributes' ), $content, $attribute, $idml );
                                        if ( !empty( $handlerAttributes ) )
                                        {
                                            foreach ( $handlerAttributes as $id => $value )
                                            {
                                                $content->setAttribute( $id, $value );
                                            }
                                        }
                                    }
                                    
                                    
                                    if ( $content->haveChild() )
                                    {
                                        $childStrlen = eZIdmlPackageHandler::importChildEzContents( $content, $children, $attribute, $idml );
                                        
                                        if ( $content->hasAttribute( 'char_length' ) )
                                        {
                                            $newStrlen = $content->attribute( 'char_length' ) - $childStrlen;
                                            $content->setAttribute( 'char_length', $newStrlen );
                                        }
                                        
                                    }

                                    if ( !$idml->hasAttribute( 'have_contents' ) )
                                        $idml->setAttribute( 'have_contents', 1 );
                                }
                            }
                        }
                    }
                    
                }
                
                eZIdmlPackageHandler::regenerateSVGFiles( $idml );
                
            }
            
            $sourceObjectID = $sourceNode->attribute( 'contentobject_id' );
            eZContentCacheManager::clearContentCache( $sourceObjectID );
            
            return true;
        }
        
        return false;
    }
   
    public static function importChildEzContents( eZIdmlContent &$content, $childrenNodes, eZContentObjectAttribute $attribute, $idml )
    {
        $strLen = 0;
        foreach( $content->attribute( 'children' ) as $id => $child )
        {
            if ( $child instanceof eZIdmlContent )
            {
                if ( $child->hasAttribute( 'class_identifier' ) && $child->hasAttribute( 'attribute_identifier' ) )
                {
                    foreach ( $childrenNodes as $node )
                    {
                        if ( $node->attribute( 'priority' ) == $child->attribute( 'priority' ) )
                        {
                            $dataMap = $node->dataMap();
                            if ( isset( $dataMap[ $child->attribute( 'attribute_identifier' ) ] ) )
                            {
                                if ( $dataMap[ $child->attribute( 'attribute_identifier' ) ] instanceof eZContentObjectAttribute )
                                {
                                    $child->setAttribute( 'eZContentObjectTreeNodeID', $node->attribute( 'node_id' ) );
                                    $child->setAttribute( 'eZContentObjectAttributeID', $dataMap[ $child->attribute( 'attribute_identifier' ) ]->attribute( 'id' ) );
                                }
                                
                                if ( $dataMap[ $child->attribute( 'attribute_identifier' ) ]->attribute( 'data_type_string' ) == 'ezimage' )
                                {
                                    $image = explode( '|', $dataMap[ $child->attribute( 'attribute_identifier' ) ]->toString() );
                                    $child->setAttribute( 'href', $image[0] );
                                    $child->setAttribute( 'alternative_text', $image[1] );
                                }
                                else
                                {
                                    $char_length = $dataMap[ $child->attribute( 'attribute_identifier' ) ]->toString();
                                    $char_length = preg_replace( '#\s+#',' ', $char_length );
                                    $child->setAttribute( 'char_length', strlen( trim( strip_tags( $char_length ) ) ) );
                                    $strLen += strlen( $char_length );
                                }
                            }
                        }
                    }
                }
                elseif ( $child->hasAttribute( 'xmltag' ) )
                {
                    $child->setAttribute( 'eZContentObjectTreeNodeID', $content->attribute( 'eZContentObjectTreeNodeID' ) );
                    $child->setAttribute( 'eZContentObjectAttributeID', $content->attribute( 'eZContentObjectAttributeID' ) );

                    $node = eZContentObjectTreeNode::fetch( $content->attribute( 'eZContentObjectTreeNodeID' ) );

                    if ( $node instanceof eZContentObjectTreeNode )
                    {
                        $tmpDom = new DOMDocument;
                        $tmpXpath = false;
                        $imageID = false;

                        if ( $attribute->hasContent() )
                        {
                            if ( $tmpDom->loadXML( $attribute->attribute( 'data_text' ) ) )
                            {
                                $tmpXpath = new DOMXPath( $tmpDom );
                            }
                        }                        
                        
                        if ( $child->isImage() )
                        {
                            if ( $tmpXpath !== false )
                            {
                                $xquery = 'descendant::embed[@size]';
                                $tmpNodeList = $tmpXpath->query( $xquery );
                                $xmlTagPriority =  (int) $child->attribute( 'xmltag_priority' ) - 1;        
                                if ( $tmpNodeList->length >= $child->attribute( 'xmltag_priority' ) )
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
                                                $child->setAttribute( 'href', $image[0] );
                                                $child->setAttribute( 'alternative_text', $image[1] );
                                            }
                                        }
                                    }
                                }
                            }
                            /*
                            else
                            {
                                $object = $node->object();
                                $relatedObjects = $object->relatedObjects( false, false, 0, false, array( 'AllRelations' => eZContentObject::RELATION_EMBED ), false );
                                $i = 1;    
                                
                                foreach( $relatedObjects as $object )
                                {
                                    if ( $object->attribute( 'class_identifier' ) == 'image' )
                                    {
                                        $dataMap = $object->dataMap();
                                        foreach( $dataMap as $att )
                                        {
                                            if ( $att->attribute( 'data_type_string' ) == 'ezimage' )
                                            {
                                                
                                                if ( $i == $child->hasAttribute( 'xmltag_priority' ) )
                                                {
                                                    $image = explode( '|', $att->toString() );
                                                    $child->setAttribute( 'href', $image[0] );
                                                    $child->setAttribute( 'alternative_text', $image[1] );                                             
                                                    $i++;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            */
                        }
                        else
                        {
                            if ( $tmpXpath !== false )
                            {
                                $eZTagName = 'custom[@name="' . $child->attribute( 'xmltag' ) . '"]';
                                $tmpNodeList = $tmpXpath->query( 'descendant::' . $eZTagName );
                                $xmlTagPriority =  (int) $child->attribute( 'xmltag_priority' ) - 1;        
                                if ( $tmpNodeList->length && $tmpNodeList->length >= $child->attribute( 'xmltag_priority' ) )
                                {
                                    $eZTagValue = $tmpNodeList->item( $xmlTagPriority )->nodeValue;
                                    $child->setAttribute( 'char_length', strlen( $eZTagValue ) );
                                    $strLen += strlen( $eZTagValue );
                                }
                            }
                        }
                    
                        if ( $child->hasAttribute( 'handler' ) )
                        {
                            $attributeHandler = $child->attribute( 'handler' );
                            $handlerAttributes = call_user_func( array( $attributeHandler, 'setIdmlContentAttributes' ), $child, $attribute, $idml );
                            if ( !empty( $handlerAttributes ) )
                            {
                                foreach ( $handlerAttributes as $id => $value )
                                {
                                    $child->setAttribute( $id, $value );
                                }
                            }
                        }
                        
                    }
                }
            }
        }
        
        return $strLen;
    }
    
    private function extractStoriesData()
    {
        $designmaps = $this->getXMLFile( 'designmap.xml' );
        $designmap = is_array( $designmaps ) ? $designmaps[0] : $designmaps;

        //$xmlDesignmap = simplexml_load_file( $designmap );
        $xmlDesignmap = new DOMDocument( '1.0', 'utf-8' );
        $xmlDesignmap->formatOutput = true;
        $xmlDesignmap->load( $designmap );
        
        //$xmlDesignmapStories = $xmlDesignmap->xpath('//idPkg:Story');
        $xmlDesignmapXpath = new DOMXPath( $xmlDesignmap );
        $xmlDesignmapStories = $xmlDesignmapXpath->query('//idPkg:Story');

        foreach ( $xmlDesignmapStories as $designmapStory )
        {
            $source = (string) $designmapStory->getAttribute( 'src' );
            if ( $this->getXMLFile( $source ) )
            {
                $idmlContent = new eZIdmlContent();
                
                //$xmlStory = simplexml_load_file( $this->getXMLFile( $source ) );
                $xmlStory = new DOMDocument( '1.0', 'utf-8' );
                $xmlStory->formatOutput = true;
                $xmlStory->load( $this->getXMLFile( $source ) );

                //$xmlStoryItems = $xmlStory->xpath('//Story');
                $xpath = new DOMXPath( $xmlStory );
                $xmlStoryItems = $xpath->query('//Story');
                
                foreach ( $xmlStoryItems as $story )
                {
                    $idmlContent->setAttribute( 'id', (string) $story->getAttribute( 'Self' ) );
                    
                    //$xmlStoryXmlElement = $story->xpath('descendant::XMLElement');
                    $xmlStoryXmlElement = $xpath->query('//Story/descendant::XMLElement');
                    
                    foreach ( $xmlStoryXmlElement as $xmlElement )
                    {
                        //$xmlStoryContent = $story->xpath('descendant::XMLElement/descendant::Content');;
                        $xmlStoryContent = $xmlElement->getElementsByTagName( 'Content' );
                        
                        $storyContent = array();
                        $haveContent = false;
                        if ( $xmlStoryContent->length )
                        {
                            foreach ( $xmlStoryContent as $c )
                            {
                                $storyContent[] = strip_tags( $c->nodeValue );
                                $haveContent = true;
                            }
                        }
                        $storyContent = (int) strlen( implode( eZIdmlContent::PARAGRAPH_SEPARATOR , $storyContent ) );
                        
                        if ( (string) $xmlElement->getAttribute( 'XMLContent' ) == (string) $story->getAttribute( 'Self' )  )
                        {
                            $idmlContent->setAttribute( 'xml_file', $source );
                            $idmlContent->setAttribute( 'tag', (string) $xmlElement->getAttribute( 'MarkupTag') );
                            $idmlContent->setAttribute( 'original_char_length',  $storyContent );
                        }
                        else
                        {   
                            $params = array( 'original_char_length' => $storyContent );
                            $idmlContent->addStoryChild( (string) $xmlElement->getAttribute( 'XMLContent' ), (string) $xmlElement->getAttribute( 'MarkupTag' ), $this->idml, $params );
                        }
                        
                        $this->addItemsTagArray( (string) $xmlElement->getAttribute( 'XMLContent' ), (string) $xmlElement->getAttribute( 'MarkupTag' ) );
                    }

                    //$idmlContent->setAttribute( 'original_text', $storyContent );
                }
                $this->idml->addContent( $idmlContent );
            }
        }
    }
    
    private function extractSpreadsData()
    {
        //@TODO uniformare il SimpleXML a DOMDocument
        
        $designmaps = $this->getXMLFile( 'designmap.xml' );
        $designmap = is_array( $designmaps ) ? $designmaps[0] : $designmaps;

        $xmlDesignmap = simplexml_load_file( $designmap );
        
        $xmlDesignmapSpreads = $xmlDesignmap->xpath('//idPkg:Spread');
        foreach ( $xmlDesignmapSpreads as $designmapSpread )
        {
            $source = (string) $designmapSpread['src'];
            if ( $this->getXMLFile( $source ) )
            {
                $idmlSpread = new eZIdmlSpread();
                
                $xmlSpread = simplexml_load_file( $this->getXMLFile( $source ) );
                $xmlSpreadSpreads = $xmlSpread->xpath('//Spread');
                foreach ( $xmlSpreadSpreads as $spread )
                {                    
                    $idmlSpread->setAttribute( 'id', (string) $spread['Self'] );
                    $itemTransormations = $this->searchItemTransform( $spread );
                    $idmlSpread->setAttribute( 'itemTransform', $itemTransormations );
                    
                    $xmlSpreadPages = $spread->xpath('//Page');
                    foreach ( $xmlSpreadPages as $page )
                    {
                        $idmlPage = new eZIdmlPage();
                        
                        $idmlPage->setAttribute( 'id', (string) $page['Self'] );
                        
                        $itemTransformations = $this->searchItemTransform( $page );
                        $idmlPage->setAttribute( 'itemTransform', $itemTransformations );
                        
                        $dimensions = $this->getPageDimensions();
                        $idmlPage->setAttribute( 'height', $dimensions['height'] );
                        $idmlPage->setAttribute( 'width', $dimensions['width'] );
                        
                        /*
                        $dim = (string) $page['GeometricBounds'];
                        $dim = explode( ' ', $dim );
                        if ( isset( $dim[2] ) && isset( $dim[3] ))
                        {
                            $idmlPage->setAttribute( 'height', (string) $dim[2] );
                            $idmlPage->setAttribute( 'width', (string) $dim[3] );
                        }
                        */
                        
                        $idmlSpread->addPage( $idmlPage );
                    }
                    
                    foreach ( $this->itemsToShape as $key => $value )
                    {
                    
                        $xmlSpreadItems = $spread->xpath( '//' . $key );
                        foreach ( $xmlSpreadItems as $item )
                        {
                            $idmlItem = new eZIdmlPageItem();
                            $idmlItem->setAttribute( 'id', (string) $item['Self'] );
                            $idmlItem->setAttribute( 'type', $key );
                            $idmlItem->setAttribute( 'shape', $value );
                            $style = array(
                                'fill' => 'none',
                                'stroke' => 'black',
                                'stroke-width' => '1',
                                'fill-opacity' => '0.05',
                                'stroke-opacity' => '0.5'
                            );
                            $renderContent = array();
                            
                            // a rectangle contains a image    
                            if ( count( $item->xpath( 'child::Image' ) ) > 0 )
                            {
                                // @TODO mask image                                
                                //$idmlItem->setAttribute( 'useAsMask', true );
                                $childImage = $item->xpath( 'child::Image' );
                                if ( isset( $this->itemsTagArray[ (string) $childImage[0]['Self'] ] ) )
                                {
                                    $tag = $this->itemsTagArray[ (string) $childImage[0]['Self'] ];
                                    $style['fill'] = $style['stroke'] = $this->tagsColorArray[ $tag ];
                                    $style['stroke-width'] = 2;
                                    $renderContent['type'] =  'image_container';
                                    $renderContent['imageID'] =  (string) $childImage[0]['Self'];
                                }
                            }
                            
                            // a image is always inside a rectangle
                            if ( $value == 'image' )
                            {
                                $renderContent['type'] =  'image';
                                
                                $idmlContent = new eZIdmlContent();
                                $idmlContent->setAttribute( 'id', (string) $item['Self'] );
                                $idmlContent->setAttribute( 'type', 'image' );
                                $link = $item->xpath( 'descendant::Link[@LinkResourceURI]' );
                                $idmlContent->setAttribute( 'original_href', (string) $link[0]['LinkResourceURI'] );
                                if ( isset( $this->itemsTagArray[ (string) $item['Self'] ] ) )
                                {
                                    $tag = $this->itemsTagArray[ (string) $item['Self'] ];
                                    $idmlContent->setAttribute( 'tag', $tag );
                                    $idmlContent->setAttribute( 'spread_id', (string) $spread['Self'] );
                                    $style = array();
                                }
                                $this->idml->addContent( $idmlContent );
                                
                                // @TODO mask image
                                //$parent = $item->xpath( 'parent::Rectangle' );
                                //$style = array( 'mask' => 'url(#mask' . (string) $parent[0]['Self'] . ')' );
                                
                                $graphicsBounds = $item->xpath( 'descendant::GraphicBounds' );
                                $idmlItem->setAttribute( 'width', (int) $graphicsBounds[0]['Right'] - (int) $graphicsBounds[0]['Left'] );
                                $idmlItem->setAttribute( 'height', (int) $graphicsBounds[0]['Bottom'] - (int) $graphicsBounds[0]['Top'] );
                                $idmlItem->setAttribute( 'xy', array( (int) $graphicsBounds[0]['Left'], (int) $graphicsBounds[0]['Top'] ) );
                            }
                            
                            $itemCoordinates = $this->searchPathPointTypeAnchor( $item );
                            $idmlItem->setAttribute( 'coordinates', $itemCoordinates );
                            
                            // item contains text: usually is a TextFrame
                            if ( (string) $item['ContentType'] == "TextType" )
                            {
                                //is a rectangle?
                                $insetSpacing = $item->xpath( 'descendant::TextFramePreference/descendant::InsetSpacing' );
                                
                                if ( count( $insetSpacing ) )
                                {
                                    if ( $insetSpacing[0]['type'] == 'unit' && count( $itemCoordinates ) == 4 )
                                    {
                                        if ( $itemCoordinates[0][0] != $itemCoordinates[1][0] && $itemCoordinates[2][0] != $itemCoordinates[3][0] )
                                            $idmlItem->setAttribute( 'shape', 'ellipse' );
                                    }
                                }
                                
                                $renderContent['type'] =  'text';
                                
                                if ( count( $item->xpath( 'descendant::TextFramePreference[@TextColumnFixedWidth]' ) ) > 0 )
                                {
                                    $width = $item->xpath( 'descendant::TextFramePreference[@TextColumnFixedWidth]' );
                                    $idmlItem->setAttribute( 'width', (string) $width[0]['TextColumnFixedWidth'] );
                                }
                                
                                if ( $ParentStory = (string) $item['ParentStory'] )
                                {
                                    $idmlItem->setAttribute( 'ParentStory', $ParentStory );
                                    $storyContent = $this->idml->getContent( $ParentStory );
                                    if ( $storyContent )
                                    {
                                        if ( $tag = $storyContent->hasTag() )
                                        {
                                            $style['fill'] = $style['stroke'] = $this->tagsColorArray[ $tag ];
                                            $style['stroke-width'] = 2;
                                        }
                                    }
                                }
                                                                
                                if ( (string) $item['PreviousTextFrame'] != 'n' )
                                    $idmlItem->setAttribute( 'PreviousTextFrame', (string) $item['PreviousTextFrame'] );
                                
                                if ( (string) $item['NextTextFrame'] != 'n' )
                                    $idmlItem->setAttribute( 'NextTextFrame', (string) $item['NextTextFrame'] );
                            }
                            
                            // @TODO bezier line
                            /*
                            $GeometryPathType = $item->xpath( 'descendant::GeometryPathType' );
                            if ( $value == 'polygon' && $GeometryPathType[0]['PathOpen'] == 'true' )
                            {
                                $idmlItem->setAttribute( 'shape', 'path' );
                            }
                            */
                            if ( !( empty( $renderContent ) ) )
                                $idmlItem->setAttribute( 'renderContent', $renderContent );
                            
                            $itemTransformations = $this->searchItemTransform( $item );
                            $idmlItem->setAttribute( 'itemTransform', $itemTransformations );
                            $idmlItem->setAttribute( 'style', $style );
                            $idmlSpread->addItem( $idmlItem );
                        }
                       
                    }
                }
                
                $this->idml->addSpread( $idmlSpread );
            }
        }
    }
    
    public static function regenerateSVGFiles( $idml )
    {       
        if ( $idml->hasAttribute( 'svg_files' ) )
        {

            $svgFiles = $idml->attribute( 'svg_files' );
            foreach ( $svgFiles as $svgFile )
            {
                eZIdmlPackageHandler::removeFile( $svgFile );
            } 
            
            $countPage = 1;
            foreach( $idml->attribute( 'spreads' ) as $id => $spread )
            {
                $svg = new eZIdmlSvg( $spread, $idml, array( 'countPage' => $countPage ) );
                
                $htmlContainerDimensions[ $id ] = $svg->getSpreadDimensions();          
                $filename = 'Spread_' . $id . '_';
                $filename .= intval( $htmlContainerDimensions[ $id ]['width'] ) . 'x';
                $filename .= intval( $htmlContainerDimensions[ $id ]['height'] ) . '.svg';
                
                $directory = eZDir::dirpath( $idml->attribute( 'idml_file' ) );
                $data = $svg->output();
                eZFile::create( $filename, $directory, $data, false );
                eZLog::write( 'Regenerated svg ' . $filename, eZIdmlPackageHandler::LOGFILENAME );
                $countPage = $svg->countPage++;
            }
        }
    }
    
    private function createSvgFiles()
    {
        $htmlContainerDimensions = array();
        $countPage = 1;
        foreach( $this->idml->attribute( 'spreads' ) as $id => $spread )
        {
            $svg = new eZIdmlSvg( $spread, $this->idml, array( 'countPage' => $countPage ) );
            
            $htmlContainerDimensions[ $id ] = $svg->getSpreadDimensions();
            $spread->setAttribute( 'width', $htmlContainerDimensions[ $id ]['width'] );
            $spread->setAttribute( 'height', $htmlContainerDimensions[ $id ]['height'] );
            
            $filename = 'Spread_' . $id . '_';
            $filename .= intval( $htmlContainerDimensions[ $id ]['width'] ) . 'x';
            $filename .= intval( $htmlContainerDimensions[ $id ]['height'] ) . '.svg';
            
            $directory = $this->getDirPath();
            $data = $svg->output();
            eZFile::create( $filename, $directory, $data, false );
            
            $this->SVGfiles[ $id ] = $directory . eZSys::fileSeparator() . $filename;
            $countPage = $svg->countPage++;
        }
        $this->idml->setAttribute( 'svg_files', $this->getSVGFiles() );
    }
    
    private function searchItemTransform( SimpleXMLElement $element )
    {
        $transformations = array();
        $parents = $element->xpath( 'ancestor-or-self::*[@ItemTransform]' );
        foreach ( $parents as $node )
        {
            $transformations[] = (string) $node['ItemTransform'];
        }
        return $transformations;
    }
    
    private function searchPathPointTypeAnchor( SimpleXMLElement $element )
    {
        $coordinates = array();
        $pathPointTypeAnchor = $element->xpath( 'descendant::PathGeometry/descendant::PathPointType[@Anchor]' );
        foreach ( $pathPointTypeAnchor as $node )
        {
            $coordinates[] = explode( ' ', (string) $node['Anchor'] );
        }
        return $coordinates;
    }

   private function extractTagsData()
    {
        //@TODO uniformare il SimpleXML a DOMDocument
        
        $designmaps = $this->getXMLFile( 'designmap.xml' );
        $designmap = is_array( $designmaps ) ? $designmaps[0] : $designmaps;
        $xmlDesignmap = simplexml_load_file( $designmap );
        $xmlDesignmapTags = $xmlDesignmap->xpath('//idPkg:Tags');
        foreach ( $xmlDesignmapTags as $designmapTag )
        {
            $source = (string) $designmapTag['src'];
            if ( $this->getXMLFile( $source ) )
            {
                $xmlTag = simplexml_load_file( $this->getXMLFile( $source ) );
                $xmlTags = $xmlTag->xpath('//XMLTag');
                foreach ( $xmlTags as $tag )
                {
                    $color = $tag->xpath( 'descendant::TagColor' );
                    $this->tagsColorArray[ (string) $tag['Name'] ] = (string) $color[0];
                }
            }
        }
        $xmlDesignmapBackingStories = $xmlDesignmap->xpath('//idPkg:BackingStory');
        foreach ( $xmlDesignmapBackingStories as $designmapBackingStory )
        {
            $source = (string) $designmapBackingStory['src'];
            if ( $this->getXMLFile( $source ) )
            {   
                $xmlTag = simplexml_load_file( $this->getXMLFile( $source ) );
                $xmlTags = $xmlTag->xpath('//XMLElement[@XMLContent]');
                foreach ( $xmlTags as $tag )
                {
                    $this->addItemsTagArray( (string) $tag['XMLContent'], (string) $tag['MarkupTag'] );
                }
            }
        }
    }
    
    public function addSourceNode( $selectedNodeID )
    {
        if ( is_array( $selectedNodeID ) )
            $selectedNodeID = $selectedNodeID[0];
        
        $sourceNode = eZContentObjectTreeNode::fetch( $selectedNodeID );
        
        if ( is_object( $sourceNode ) )
        {
            $contentObjectID = $this->ContentObject->attribute( 'id' );
            $this->removeSourceNode();
            
            $sourceObject = $sourceNode->object();
            //$sourceObject->appendInputRelationList( array( $contentObjectID ), eZContentObject::RELATION_COMMON );
            //$sourceObject->commitInputRelations( $sourceObject->CurrentVersion );
            $sourceObject->addContentObjectRelation( $contentObjectID, $sourceObject->CurrentVersion );

            eZLog::write( 'Adding common relation from object #' . $sourceObject->attribute( 'id' ) . ' to object #' . $contentObjectID , eZIdmlPackageHandler::LOGFILENAME );

            $this->idml->setAttribute( 'source_node_id',  $sourceNode->attribute( 'node_id' ) );
            $this->idml->setAttribute( 'modified_source_node',  1 );
            eZLog::write( 'Add source node "' . $sourceNode->attribute( 'name' ) . '" #' . $sourceNode->attribute( 'node_id' )  . ' to ContentObjectAttribute #' . $this->ContentObjectAttributeData['id'] , eZIdmlPackageHandler::LOGFILENAME );

        }
    }
    
    public function removeSourceNode()
    {
        if ( $this->idml->hasAttribute( 'source_node_id') )
        {
            $contentObjectID = $this->ContentObject->attribute( 'id' );
            $sourceNode = eZContentObjectTreeNode::fetch( $this->idml->attribute( 'source_node_id') );
            if ( $sourceNode )
            {
                $sourceObject = $sourceNode->object();
                $sourceObject->removeContentObjectRelation( $contentObjectID, $sourceObject->CurrentVersion );
                eZLog::write( 'Removing common relation from object #' . $sourceObject->attribute( 'id' ) . ' to object #' . $contentObjectID , eZIdmlPackageHandler::LOGFILENAME );
            }
        }
    }

    public function setImportEzContents()
    {
        $this->idml->setAttribute( 'import_ez_contents',  1 );
    }

    public function unsetImportEzContents()
    {
        $this->idml->removeAttribute( 'import_ez_contents' );
    }
    
    private function addItemsTagArray( $StoryID, $tag )
    {
        $value = eZIdmlContent::cleanTagName( $tag );
        $this->itemsTagArray[$StoryID] = $value;
    }
    
    private function getPageDimensions()
    {
        $source = 'Resources/Preferences.xml';
        if ( $this->getXMLFile( $source ) )
        {
            $preferences = simplexml_load_file( $this->getXMLFile( $source ) );
            $height = $preferences->xpath('/idPkg:Preferences/DocumentPreference/@PageHeight');
            $width = $preferences->xpath('/idPkg:Preferences/DocumentPreference/@PageWidth');
            
            return array(
                'height' => (string) $height[0],
                'width' => (string) $width[0]
            );
        }
        
        return false;
    }

} 

?>
