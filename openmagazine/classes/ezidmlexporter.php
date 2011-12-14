<?php
/**
 * File containing the eZIdmlExporter class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlExporter
{
    public $node;

    public $sectionClasses;

    public $isContainer;

    public $isSection;
    
    public $languageCode;

    const NEWDIRECTORY = 'export';

    const LOGFILENAME = 'idml_export.log';

    public static $COMPLEX_DATATYPES = array( 'ezxmltext', 'ezmatrix' );
    
    function __construct( $node, $languageCode )
    {
        $idmlINI = eZINI::instance( 'openmagazine.ini' );
        $this->node = $node;
        
        $containerClasses = $idmlINI->hasVariable( 'ClassSettings', 'Container' ) ? $idmlINI->variable( 'ClassSettings', 'Container' ) : array();
        $this->sectionClasses = $idmlINI->hasVariable( 'ClassSettings', 'Section' ) ? $idmlINI->variable( 'ClassSettings', 'Section' ) : array();
        
        $this->isContainer = in_array( $this->node->attribute( 'class_identifier' ), $containerClasses  );
        $this->isSection = in_array( $this->node->attribute( 'class_identifier' ), $this->sectionClasses  );
        $this->languageCode = $languageCode;
        
    }
    
    function haveImages()
    {
        if ( !$this->isContainer )
        {
            $node = $this->node;
            
            $dataMap = $node->dataMap();
            $ContentObjectAttribute = false;
            foreach( $dataMap as $attribute )
            {
                if ( $attribute->attribute( 'data_type_string' ) == 'ezidml' )
                {
                    $ContentObjectAttribute = $attribute;
                    break;
                }
                    
            }
            if ( !$ContentObjectAttribute )
            {
                eZLog::write( 'eZIDML ContentObjectAttribute not found in node #' . $node->attribute( 'node_id' ), eZIdmlExporter::LOGFILENAME );
                return false;
            }
            
            $idmlPackageHandler = new eZIdmlPackageHandler( $ContentObjectAttribute );
            $sourceID = $idmlPackageHandler->idml->attribute( 'source_node_id' );
            $source = eZContentObjectTreeNode::fetch( $sourceID, $this->languageCode );
            $images = self::getImagesFromNodes( array( $source ), true );
            return count( $images ) > 0;
        }
        return false;
    }
    
    function debugIdml()
    {
        $node = $this->node;
        eZLog::write( 'show debugIdml for node ' . $node->attribute( 'name' ) . ' (#' . $node->attribute( 'node_id' ) . ')', eZIdmlExporter::LOGFILENAME );
        
        $dataMap = $node->dataMap();
        $ContentObjectAttribute = false;
        foreach( $dataMap as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) == 'ezidml' )
            {
                $ContentObjectAttribute = $attribute;
                break;
            }
                
        }
        if ( !$ContentObjectAttribute )
        {
            eZLog::write( 'eZIDML ContentObjectAttribute not found in node #' . $node->attribute( 'node_id' ), eZIdmlExporter::LOGFILENAME );
            return false;
        }
        
        $idmlPackageHandler = new eZIdmlPackageHandler( $ContentObjectAttribute );
        return $idmlPackageHandler->idml;
    }
    
    function execute()
    {
        if ( $this->isContainer )
        {
            return $this->runChildren();
        }
        else
        {
            $output = array( 'files' => array() );
            $resultNode = eZIdmlExporter::run( $this->node, $this->languageCode );
            $output[ 'files' ][] = $resultNode;
            return $output;
        }
    }
    
    public static function run( $node, $languageCode )
    {
        eZLog::write( '==== Start run process for node ' . $node->attribute( 'name' ) . ' (#' . $node->attribute( 'node_id' ) . ') ====', eZIdmlExporter::LOGFILENAME );

        $output = array();
        $temp = array();

        $dataMap = $node->dataMap();
        $ContentObjectAttribute = false;
        foreach( $dataMap as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) == 'ezidml' )
            {
                $ContentObjectAttribute = $attribute;
                break;
            }
                
        }
        if ( !$ContentObjectAttribute )
        {
            eZLog::write( 'eZIDML ContentObjectAttribute not found in node #' . $node->attribute( 'node_id' ), eZIdmlExporter::LOGFILENAME );
            return false;
        }
        
        $idmlPackageHandler = new eZIdmlPackageHandler( $ContentObjectAttribute );

        $directory = $idmlPackageHandler->getDirPath() . eZSys::fileSeparator() . eZIdmlExporter::NEWDIRECTORY;
        if ( !file_exists( $directory ) )
        {
            eZDir::mkdir( $directory, false, true );
        }
        
        $pathString = $node->pathWithNames();
        $pathString = function_exists( 'mb_strtolower' ) ? mb_strtolower( $pathString ) : strtolower( $pathString );
        $pathArray = explode( '/', $pathString );
        
        $contentTree = $idmlPackageHandler->idml->contentTree();
        
        $temp['tree'] = $contentTree;
        $temp['files'] = $idmlPackageHandler->getXMLFiles();         
        $temp['nodes'] = array();
        $temp['images'] = array();
        
        foreach ( $contentTree as $id => $contentArray )
        {
            foreach ( $contentArray as $idmlContent )
            {
                if ( $idmlContent->hasAttribute( 'xml_file' ) )
                {
                    if ( $idmlPackageHandler->getXMLFile( $idmlContent->attribute( 'xml_file' ) ) )
                    {

                        $storeParams = array(
                                'filename' => basename( $idmlContent->attribute( 'xml_file' ) ),
                                'directory' => $directory . eZSys::fileSeparator() . 'Stories'
                            );
                        
                        if ( $idmlContent->hasAttribute( 'have_child' ) )
                        {
                            $dom = new DOMDocument( '1.0', 'utf-8' );
                            $dom->formatOutput = true;
                            $dom->load( $idmlPackageHandler->getXMLFile( $idmlContent->attribute( 'xml_file' ) ) );
        
                            eZLog::write( 'Read story ' . $idmlContent->attribute( 'id' ) . ' with file ' . $idmlContent->attribute( 'xml_file' ) . ' - ' . $idmlPackageHandler->getXMLFile( $idmlContent->attribute( 'xml_file' ) ), eZIdmlExporter::LOGFILENAME );

                            $xpath = new DOMXPath( $dom );
                            $XMLElements = $xpath->query('descendant::XMLElement');
        
                            foreach( $XMLElements as $XMLElement )
                            {
                                $storyID = false;
                                $isImage = false;
                                
                                if ( $XMLElement->hasAttribute( 'XMLContent' ) )
                                {
                                    $storyID = $XMLElement->getAttribute( 'XMLContent' );
                                    $XMLAttributes = $xpath->query('descendant::XMLElement[@XMLContent="' . $storyID . '"]/child::XMLAttribute');
                                    $isImage = ( $XMLAttributes->length  > 0 );
                                }
                                
                                if( $storyID && !$isImage)
                                {
                                    if ( $storyID == $idmlContent->attribute( 'id' ) )
                                    {
                                        //sono già nella storia giusta
                                        if ( $idmlContent->hasAttribute( 'eZContentObjectTreeNodeID' ) )
                                        {
                                            $idmlContentObjectTreeNode = eZContentObjectTreeNode::fetch( $idmlContent->attribute( 'eZContentObjectTreeNodeID' ), $languageCode );
                                            
                                            if ( !$idmlContentObjectTreeNode )
                                                $idmlContentObjectTreeNode = eZContentObjectTreeNode::fetch( $idmlContent->attribute( 'eZContentObjectTreeNodeID' ) );
                                            
                                            if ( $idmlContentObjectTreeNode )
                                                $currentVersion = $idmlContentObjectTreeNode->object()->currentVersion();
                                        }
                                
                                        if ( $idmlContentObjectTreeNode && $idmlContent->hasAttribute( 'eZContentObjectAttributeID' ) )
                                        {
                                            $idmlContentObjectAttribute = eZContentObjectAttribute::fetch( $idmlContent->attribute( 'eZContentObjectAttributeID' ), $currentVersion->attribute( 'version' ) );
                                            
                                            if ( !$idmlContentObjectAttribute )
                                            {
                                                eZLog::write( 'eZContentObjectAttribute #' . $idmlContent->attribute( 'eZContentObjectAttributeID' ) . ' not found in node #' . $idmlContent->attribute( 'eZContentObjectTreeNodeID' ), eZIdmlExporter::LOGFILENAME );
                                                continue;
                                            }
                                
                                            eZLog::write( 'Process self attribute of  ' . $storyID, eZIdmlExporter::LOGFILENAME );
                                            eZIdmlExporter::processAttribute( $idmlContent, $idmlContentObjectAttribute, $xpath, $dom );
                                        }
                                    }
                                    else
                                    {
                                        //cerco e mdifico la storia figlia
                                        if ( $idmlContent->haveChild( $storyID ) )
                                        {
                                            $childContent = $idmlContent->getChild( $storyID );
                                
                                            if ( $childContent->hasAttribute( 'xml_file' ) )
                                            {
                                                eZLog::write( 'Select child of ' . $idmlContent->attribute( 'id' ) . ' with own xmlfile ' . $storyID, eZIdmlExporter::LOGFILENAME );

                                                $childStoreParams = array(
                                                    'filename' => basename( $childContent->attribute( 'xml_file' ) ),
                                                    'directory' => $directory . eZSys::fileSeparator() . 'Stories'
                                                );

                                                if ( isset( $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ 'Stories' . eZSys::fileSeparator() . $childStoreParams['filename' ] ]  ) )
                                                {
                                                    $childStoreParams = false;
                                                }

                                                if ( eZIdmlExporter::processStoryFile( $idmlPackageHandler, $childContent, $output, $temp['nodes'], $childStoreParams ) )
                                                {
                                                    $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ 'Stories' . eZSys::fileSeparator() . $childStoreParams['filename'] ] = $childStoreParams['directory'] . eZSys::fileSeparator() . $childStoreParams['filename'];
                                                }

                                            }
                                            else
                                            {
                                                eZLog::write( 'Error processing child of ' . $idmlContent->attribute( 'id' ) . ' with ID #' . $storyID, eZIdmlExporter::LOGFILENAME );
                                            }
                                        }
                                    }
                                }
                                elseif ( !$storyID && !$isImage )
                                {
                                    // cerco e modifico la storia figlia nel file del genitore
                                    if( $XMLElement->hasAttribute( 'MarkupTag' ) )
                                    {
                                        $tag = $XMLElement->getAttribute( 'MarkupTag' );
                                        $tag = eZIdmlContent::cleanTagName( $tag );
                                        $idmlChildContent = $idmlPackageHandler->idml->getContentByTagName( $tag, $idmlContent->attribute( 'id' ) );
                                        
                                        if ( $idmlChildContent instanceof eZIdmlContent )
                                        {

                                            if ( $idmlChildContent && $idmlChildContent->hasAttribute( 'eZContentObjectTreeNodeID' ) )
                                            {
                                                $idmlChildContentObjectTreeNode = eZContentObjectTreeNode::fetch( $idmlChildContent->attribute( 'eZContentObjectTreeNodeID' ),$languageCode );

                                                if ( !$idmlChildContentObjectTreeNode )
                                                    $idmlChildContentObjectTreeNode = eZContentObjectTreeNode::fetch( $idmlChildContent->attribute( 'eZContentObjectTreeNodeID' ) );

                                                if ( $idmlChildContentObjectTreeNode )
                                                    $currentVersion = $idmlChildContentObjectTreeNode->object()->currentVersion();
                                            }
                                            if ( $idmlChildContentObjectTreeNode && $idmlChildContent->hasAttribute( 'eZContentObjectAttributeID' ) )
                                            {
                                                $idmlChildContentObjectAttribute = eZContentObjectAttribute::fetch( $idmlChildContent->attribute( 'eZContentObjectAttributeID' ), $currentVersion->attribute( 'version' ) );
                                                
                                                if ( !$idmlChildContentObjectAttribute )
                                                {
                                                    eZLog::write( 'eZContentObjectAttribute #' . $idmlChildContent->attribute( 'eZContentObjectAttributeID' ) . ' not found in node #' . $idmlChildContent->attribute( 'eZContentObjectTreeNodeID' ), eZIdmlExporter::LOGFILENAME );
                                                    continue;
                                                }
                                                
                                                $temp['nodes'][ $idmlChildContentObjectTreeNode->attribute( 'node_id' ) ] = $idmlChildContentObjectTreeNode;
                                                eZLog::write( 'Process attribute of  ' . $idmlChildContent->attribute( 'parent_story_id' ) , eZIdmlExporter::LOGFILENAME );
                                                eZIdmlExporter::processAttribute( $idmlChildContent, $idmlChildContentObjectAttribute, $xpath, $dom );
                                            }
                                        }
                                    }

                                }
                                elseif ( $storyID && $isImage )
                                {
                                    // scrivo l'immagine
                                    eZLog::write( 'Select child image of ' . $idmlContent->attribute( 'id' ) . ' ' . $storyID , eZIdmlExporter::LOGFILENAME );$idmlChildContent = $idmlPackageHandler->idml->getContent( $storyID );
                                    
                                    if ( $idmlChildContent instanceof eZIdmlContent )
                                    {
                                        if ( $idmlChildContent->isImage() )
                                        {
                                            // immagine embeddata nel xml della storia
                                            $XMLAttributes->item(0)->removeAttribute( 'Value' );
                                            $XMLAttributes->item(0)->setAttribute( 'Value', $idmlChildContent->getImageLocalPath( $pathArray ) );
                                            $spreadID = $idmlChildContent->attribute( 'spread_id' );
                                            
                                            if ( $idmlPackageHandler->getXMLFile( 'Spreads/Spread_' . $spreadID . '.xml' ) )
                                            {
                                                $spreadDom = new DOMDocument( '1.0', 'utf-8' );
                                                $spreadDom->formatOutput = true;
                                                $spreadFileName = 'Spreads/Spread_' . $spreadID . '.xml';
                                            
                                                if ( isset( $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ $spreadFileName ] ) )
                                                    $spreadFile = $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ $spreadFileName ];
                                                else
                                                    $spreadFile = $idmlPackageHandler->getXMLFile( 'Spreads/Spread_' . $spreadID . '.xml' );
                                            
                                                $spreadDom->load( $spreadFile );
                                                $spreadXpath = new DOMXPath( $spreadDom );
                                                $Link = $spreadXpath->query( 'descendant::Image[@Self="' . $idmlChildContent->attribute( 'id' ) . '"]/child::Link' );
                                            
                                                if ( $Link->length )
                                                {
                                                    $Link->item(0)->removeAttribute( 'LinkResourceURI' );
                                                    $Link->item(0)->setAttribute( 'LinkResourceURI', $idmlChildContent->getImageLocalPath( $pathArray ) );
                                                }
                                            
                                                $spreadStoreParams = array(
                                                    'filename' => 'Spread_' . $spreadID . '.xml',
                                                    'directory' => $directory . eZSys::fileSeparator() . 'Spreads'
                                                );
                                            
                                                $spreadData = $spreadDom->saveXML();
                                            
                                                if ( eZIdmlExporter::storeFile( $spreadData, $spreadStoreParams ) )
                                                {
                                                    $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ 'Spreads' . eZSys::fileSeparator() . $spreadStoreParams['filename'] ] = $spreadStoreParams['directory'] . eZSys::fileSeparator() . $spreadStoreParams['filename'];
                                                    $temp['images'][] = $idmlChildContent;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            
                            $data = $dom->saveXML();
                            eZIdmlExporter::storeFile( $data, $storeParams );
                            $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ 'Stories' . eZSys::fileSeparator() . $storeParams['filename'] ] = $storeParams['directory'] . eZSys::fileSeparator() . $storeParams['filename'];

                        }
                        else
                        {                            
                            if ( eZIdmlExporter::processStoryFile( $idmlPackageHandler, $idmlContent, $output, $temp['nodes'], $storeParams ) )
                            {
                                $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ 'Stories' . eZSys::fileSeparator() . $storeParams['filename'] ] = $storeParams['directory'] . eZSys::fileSeparator() . $storeParams['filename'];
                            }

                        }
                    }
                }
                elseif ( $idmlContent->isImage() )
                {
                    // immagine senza storia
                    eZLog::write( 'Process orphan image (content #' . $idmlContent->attribute( 'id' ) . ')' , eZIdmlExporter::LOGFILENAME );
                    $spreadID = $idmlContent->attribute( 'spread_id' );
                    
                    if ( $idmlPackageHandler->getXMLFile( 'Spreads/Spread_' . $spreadID . '.xml' ) )
                    {
                        $spreadDom = new DOMDocument( '1.0', 'utf-8' );
                        $spreadDom->formatOutput = true;
                        $spreadFileName = 'Spreads/Spread_' . $spreadID . '.xml';
                    
                        if ( isset( $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ $spreadFileName ] ) )
                            $spreadFile = $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ $spreadFileName ];
                        else
                            $spreadFile = $idmlPackageHandler->getXMLFile( 'Spreads/Spread_' . $spreadID . '.xml' );
                    
                        $spreadDom->load( $spreadFile );
                        $spreadXpath = new DOMXPath( $spreadDom );
                        $Link = $spreadXpath->query( 'descendant::Image[@Self="' . $idmlContent->attribute( 'id' ) . '"]/child::Link' );
                    
                        if ( $Link->length )
                        {
                            $Link->item(0)->removeAttribute( 'LinkResourceURI' );
                            $Link->item(0)->setAttribute( 'LinkResourceURI', $idmlContent->getImageLocalPath( $pathArray ) );
                        }
                    
                        $spreadStoreParams = array(
                            'filename' => 'Spread_' . $spreadID . '.xml',
                            'directory' => $directory . eZSys::fileSeparator() . 'Spreads'
                        );
                    
                        $spreadData = $spreadDom->saveXML();
                    
                        if ( eZIdmlExporter::storeFile( $spreadData, $spreadStoreParams ) )
                        {
                            $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ 'Spreads' . eZSys::fileSeparator() . $spreadStoreParams['filename'] ] = $spreadStoreParams['directory'] . eZSys::fileSeparator() . $spreadStoreParams['filename'];
                            $temp['images'][] = $idmlContent;
                        }
                    }
                }
            }
            
        }

        $ini = eZINI::instance( 'site.ini' );
        $var = $ini->variable( 'FileSettings','VarDir' );
        $pathDir = eZIdmlContent::createPath( $pathArray );
        $storageIdmlDir = $var . eZSys::fileSeparator() . 'storage' . eZSys::fileSeparator() . 'application' . eZSys::fileSeparator() . 'openmagazine' . eZSys::fileSeparator();
        $varDir =  $storageIdmlDir . $pathDir;
        
        if ( !is_dir( $varDir ) )
        {
            eZDir::mkdir( $varDir, false, true );
        }

        $findImages = array();
        
        if ( count( $temp['images'] ) )
        {
            //modifico metadata.xml per inserire i segnaposto immagine
            if ( $idmlPackageHandler->getXMLFile( 'META-INF/metadata.xml' ) )
            {
                $fileHandler = eZClusterFileHandler::instance( $idmlPackageHandler->getXMLFile( 'META-INF/metadata.xml' ) );
                $metadata = $fileHandler->fetchContents();
                
                foreach( $temp['images'] as $idmlImage )
                {
                    if ( $idmlImage->hasAttribute( 'href' ) )
                    {
                        $original = $idmlImage->attribute( 'original_href' );
                        
                        if ( strpos( $original, 'file:/' ) === 0 && strpos( $original, 'file:///' ) === false )
                            $original = str_replace( 'file:/', 'file:///', $original );

                        $metadata = str_replace( $original, $idmlImage->getImageLocalPath( $pathArray ), $metadata );
                        //eZDebug::writeNotice( $original . ' ->  ' . $idmlImage->getImageLocalPath( $pathArray ), __METHOD__ );       
        
                        $originalFile = $idmlImage->getImagePath( true );
                        $destinationFile = $varDir . $idmlImage->getImageLocalName();
                        eZFileHandler::copy( $originalFile, $destinationFile );
                        //eZDebug::writeNotice( $originalFile . ' ->  ' . $destinationFile, __METHOD__ );
                        $findImages[$originalFile] = $destinationFile;
                    }
                }

                $metadataStoreParams = array(
                    'filename' => 'metadata.xml',
                    'directory' => $directory . eZSys::fileSeparator() . 'META-INF'
                );
        
                if ( eZIdmlExporter::storeFile( $metadata, $metadataStoreParams ) )
                {
                    $temp['files'][ eZIdmlExporter::NEWDIRECTORY ][ 'META-INF' . eZSys::fileSeparator() . $metadataStoreParams['filename'] ] = $metadataStoreParams['directory'] . eZSys::fileSeparator() . $metadataStoreParams['filename'];
                }
                
            }
        }
        
        $source_name = false;
        $source = eZContentObjectTreeNode::fetch( $idmlPackageHandler->idml->attribute( 'source_node_id' ), $languageCode );

        if ( !$source )
            $source = eZContentObjectTreeNode::fetch( $idmlPackageHandler->idml->attribute( 'source_node_id' ) );
        
        if ( $source )
        {
            #$source_name = explode( '/', $source->attribute('path_identification_string') );
            $source_name = explode( '/', $source->attribute('url_alias') );
            $source_name = array_pop( $source_name );
        }
        
        #$node_name = explode( '/', $node->attribute('path_identification_string') );
        $node_name = explode( '/', $node->attribute('url_alias') );
        $node_name = array_pop( $node_name );
        
        if ( $node_name !== $source_name )
            $newFileBaseName = $node_name . '+' . $source_name;
        else
            $newFileBaseName = $node_name;
        
        $newIdmlPath = $varDir . $newFileBaseName . '.idml';


        /*
        $removeFile = eZClusterFileHandler::instance( $newIdmlPath );
        
        if ( $removeFile->exists() )
            $removeFile->delete();            
        */
        $newIdml = ezcArchive::open( $newIdmlPath, ezcArchive::ZIP );
        
        eZLog::write( 'Create idml (zip) archive ' . $newIdmlPath, eZIdmlExporter::LOGFILENAME );
        $newIdml->truncate();
        
        $filesAdded = array();
        
        foreach( $temp['files'][ eZIdmlExporter::NEWDIRECTORY ] as $id => $file )
        {
            $prefix = $idmlPackageHandler->getDirPath() . eZSys::fileSeparator() . eZIdmlExporter::NEWDIRECTORY;
            $newIdml->append( $file, $prefix );
            eZLog::write( 'Add file ' . $file . ' in idml (zip) archive ' . $newIdmlPath, eZIdmlExporter::LOGFILENAME );
            $filesAdded[] = $id;
        }
        
        foreach( $temp['files'] as $id => $file )
        {
            if ( is_array( $file ) )
                continue;
            
            if ( !in_array( $id, $filesAdded ) )
            {
                $prefix = $idmlPackageHandler->getDirPath();
                $newIdml->append( $file, $prefix );
                eZLog::write( 'Add file ' . $file . ' in idml (zip) archive ' . $newIdmlPath, eZIdmlExporter::LOGFILENAME );
                $filesAdded[] = $id;
            }
        }
        
        foreach( $temp['files'][ eZIdmlExporter::NEWDIRECTORY ] as $id => $file )
        {
           eZIdmlPackageHandler::removeFile( $file );
        }
        
        $output['idml'] = $newIdmlPath;
        
        $output['images'] = false;   

        //$findImages = eZIdmlExporter::getImagesFromNodes( $temp['nodes'] );
        

    
        $idmlINI = eZINI::instance( 'openmagazine.ini' );
        $useZip = $idmlINI->variable( 'ExportImagesSettings', 'DownloadZip' ) == 'enabled';
        $lanPath = $idmlINI->hasVariable( 'ExportImagesSettings', 'LanImagePath' );
    
        if ( $useZip )
        {    
            $imagesIdmlPath = $varDir . $newFileBaseName . '.zip';
            /*
            $removeFile = eZClusterFileHandler::instance( $imagesIdmlPath );
            if ( $removeFile->exists() )
                $removeFile->delete();
            */
            $imagesIdml = ezcArchive::open( $imagesIdmlPath, ezcArchive::ZIP );
            $imagesIdml->truncate();                
            
            if ( count( $findImages ) )
            {
                foreach( $findImages as $original => $destination )
                {   
                    $file = eZClusterFileHandler::instance( $destination );
                    if ( $file->exists() )
                        $imagesIdml->append( $destination, $storageIdmlDir );
                }
            }
            else
            {
                eZFile::create( $newFileBaseName . '-no-images-found.txt', $varDir );
                $tmpNoImageFile = $varDir . $newFileBaseName . '-no-images-found.txt';
                $imagesIdmlPath = $varDir  . $newFileBaseName . '-no-images-found.zip';
                $imagesIdml = ezcArchive::open( $imagesIdmlPath, ezcArchive::ZIP );
                $imagesIdml->truncate();
                $imagesIdml->append( $tmpNoImageFile, $storageIdmlDir );
            }
            $output['images'] = $imagesIdmlPath;        
        }
        
        if ( $lanPath )
        {
            $lanPath = $idmlINI->variable( 'ExportImagesSettings', 'LanImagePath' );
            
            $destinationDir = $lanPath . eZIdmlContent::createPath( $pathArray );
            if ( !is_dir( $destinationDir ) )
            {
                eZDir::mkdir( $destinationDir, false, true );
            }
            
            if ( count( $findImages ) )
            {
                foreach( $findImages as $original => $destination )
                {   
                    $destination = $destinationDir . basename( $destination );
                    //eZDebug::writeNotice( $original . ' ->  ' . $destination, __METHOD__ );
                    eZFileHandler::copy( $original, $destination );
                }
            }
            else
            {
                eZFile::create( $newFileBaseName . '-no-images-found.txt', $destinationDir );
            }
        }
        
        
        if ( count( $findImages ) )
        {
            foreach( $findImages as $destination )
            {               
                $removeFile = eZClusterFileHandler::instance( $destination );
                if ( $removeFile->exists() )
                {
                    $removeFile->delete();
                }
            }
        }
        else
        {
            $removeFile = eZClusterFileHandler::instance( $tmpNoImageFile );
            if ( $removeFile->exists() )
            {
                $removeFile->delete();
            }
        }
        
        $output['_debug'] = $temp;
        
        return $output;
    }
    
    private static function storeFile( $data, $params )
    {
        if ( !$params )
            return false;
        //@TODO verify params
        extract( $params );
        
        //eZDebug::writeNotice( $data, $filename );
        
        eZFile::create( $filename, $directory, $data, false );
        eZLog::write( 'Create xmlfile ' . $directory . eZSys::fileSeparator() . $filename, eZIdmlExporter::LOGFILENAME );
        return true;
    }
    
    private static function processAttribute( eZIdmlContent $idmlContent, eZContentObjectAttribute $idmlContentObjectAttribute, &$xpath, &$dom )
    {
        $complexDatatype = in_array( $idmlContentObjectAttribute->attribute( 'data_type_string' ), eZIdmlExporter::$COMPLEX_DATATYPES );
        
        if ( $idmlContent->hasAttribute( 'handler' ) )
        {
            $attributeHandler = $idmlContent->attribute( 'handler' );
            $complexDatatype = call_user_func( array( $attributeHandler, 'fetchTemplateAsXML' ), $idmlContent, $idmlContentObjectAttribute );
        }
        
        if ( $complexDatatype )
        {
            $XMLElements = $xpath->query('descendant::XMLElement[@MarkupTag="XMLTag/' . $idmlContent->attribute( 'tag' )  . '"]');
            $XMLChildElements = $xpath->query('descendant::XMLElement[@MarkupTag="XMLTag/' . $idmlContent->attribute( 'tag' )  . '"]/descendant::XMLElement');
            $XMLParagraphs = $xpath->query('descendant::XMLElement[@MarkupTag="XMLTag/' . $idmlContent->attribute( 'tag' )  . '"]/child::ParagraphStyleRange');
            $XMLCharacters = $xpath->query('descendant::XMLElement[@MarkupTag="XMLTag/' . $idmlContent->attribute( 'tag' )  . '"]/child::CharacterStyleRange');

            
            foreach( $XMLChildElements as $XMLChildElement )
            {
                $XMLElements->item(0)->appendChild( $XMLChildElement );
            }
            
            foreach( $XMLParagraphs as $XMLParagraph )
            {
                $XMLElements->item(0)->removeChild( $XMLParagraph );
            }
            
            if ( $XMLCharacters->length )
            {
                foreach( $XMLCharacters as $XMLCharacter )
                {
                    $XMLElements->item(0)->removeChild( $XMLCharacter );
                }
            }
            
            $nodeList = eZIdmlExporter::addTplData( $idmlContent, $idmlContentObjectAttribute, true );
        
            foreach( $nodeList as $node )
            {
                $nodeImported = $dom->importNode( $node, true );
                $XMLElements->item(0)->appendChild( $nodeImported );
            }  
        }
        else
        {
            $XMLContents = $xpath->query('descendant::XMLElement[@MarkupTag="XMLTag/' . $idmlContent->attribute( 'tag' )  . '"]/descendant::Content');
            $i = 0;

            foreach ( $XMLContents as $XMLcontent )
            {
                if ( $i == 0 )
                {
                    $XMLcontent->nodeValue = eZIdmlExporter::addTplData( $idmlContent, $idmlContentObjectAttribute, false );
                }
                else
                {
                    $XMLcontent->nodeValue = false;
                }
                $i++;
            }

            if ( $i == 0 )
            {
                eZLog::write( 'NOTICE: Content not found in orginal Idml TextFrame tagged ' . $idmlContent->getInfoString(), eZIdmlExporter::LOGFILENAME );
                $XMLContents = $xpath->query('descendant::XMLElement[@MarkupTag="XMLTag/' . $idmlContent->attribute( 'tag' )  . '"]');
                
                $paragraphNode = $dom->createElement( 'ParagraphStyleRange' );
                $paragraphNode->setAttribute( 'AppliedParagraphStyle', 'ParagraphStyle/$ID/NormalParagraphStyle' );
                
                $characterNode = $dom->createElement( 'CharacterStyleRange' );
                $characterNode->setAttribute( 'AppliedCharacterStyle', 'CharacterStyle/$ID/[No character style]' );
                
                $contentNode = $dom->createElement( 'Content' );
                $contentValue = $dom->createTextNode( eZIdmlExporter::addTplData( $idmlContent, $idmlContentObjectAttribute, false ) );
                
                $contentNode->appendChild( $contentValue );
                $characterNode->appendChild( $contentNode );
                $paragraphNode->appendChild( $characterNode );
                $XMLContents->item(0)->appendChild( $paragraphNode );
            }
        }
    }
    
    private static function processStoryFile( eZIdmlPackageHandler $idmlPackageHandler, eZIdmlContent $idmlContent, &$output, &$nodes, $storeParams )
    {
        $idmlContentObjectTreeNode = false;

        if ( $idmlContent->hasAttribute( 'eZContentObjectTreeNodeID' ) )
        {
            $idmlContentObjectTreeNode = eZContentObjectTreeNode::fetch( $idmlContent->attribute( 'eZContentObjectTreeNodeID' ) );

            if ( $idmlContentObjectTreeNode )
                $currentVersion = $idmlContentObjectTreeNode->object()->currentVersion();

        }

        if ( $idmlContentObjectTreeNode && $idmlContent->hasAttribute( 'eZContentObjectAttributeID' ) )
        {
            $idmlContentObjectAttribute = eZContentObjectAttribute::fetch( $idmlContent->attribute( 'eZContentObjectAttributeID' ), $currentVersion->attribute( 'version' ) );
            
            if ( !$idmlContentObjectAttribute )
            {
                eZLog::write( 'eZContentObjectAttribute #' . $idmlContent->attribute( 'eZContentObjectAttributeID' ) . ' not found in node #' . $idmlContent->attribute( 'eZContentObjectTreeNodeID' ), eZIdmlExporter::LOGFILENAME );
                continue;
            }
            
            $nodes[ $idmlContentObjectTreeNode->attribute( 'node_id' ) ] = $idmlContentObjectTreeNode;
        
            $dom = new DOMDocument( '1.0', 'utf-8' );
            $dom->formatOutput = true;

            eZLog::write( 'Read story ' . $idmlContent->attribute( 'id' ) . ' with file ' .$idmlContent->attribute( 'xml_file' ) . ' - ' . $idmlPackageHandler->getXMLFile( $idmlContent->attribute( 'xml_file' ) ), eZIdmlExporter::LOGFILENAME );

            $dom->load( $idmlPackageHandler->getXMLFile( $idmlContent->attribute( 'xml_file' ) ) );
            $xpath = new DOMXPath( $dom );

            eZLog::write( 'Process self attribute of  ' . $idmlContent->attribute( 'id' ), eZIdmlExporter::LOGFILENAME );
            eZIdmlExporter::processAttribute( $idmlContent, $idmlContentObjectAttribute, $xpath, $dom );

            $data = $dom->saveXML();

            eZIdmlExporter::storeFile( $data, $storeParams );

            return true;
        }

        return false;

    }
    
    function runChildren()
    {
        $output = array( 'files' => array() );
        $classFilters = array();
        $classes = $this->sectionClasses;

        if ( !empty( $classes ) )
        {
            $classFilters = array(
                'ClassFilterType' => 'include',
                'ClassFilterArray' => $classes
            );
        }  
        
        $childrenParams = array(
            'MainNodeOnly' => true,				
        );
        $childrenParams = array_merge( $childrenParams, $classFilters );
        $children = eZContentObjectTreeNode::subTreeByNodeID(  $childrenParams, $this->node->attribute( 'node_id' ) );

        if ( $children )
        {
            foreach( $children as $node )
            {
                $resultNode = eZIdmlExporter::run( $node );
                $output[ 'files' ][] = $resultNode;
            }
        }

        return $output;
    }
    
    static function getImagesFromNodes( $nodes = array(), $children = false )
    {
        if ( !is_array( $nodes ) )
        {
            $nodes = array( $nodes );   
        }

        $result = array();

        foreach( $nodes as $node )
        {            
            $object = $node->attribute( 'object' );
            $result = array_merge( $result, eZIdmlExporter::getImagesPathFromContentObject( $object ) );
            $relatedObjects = $object->attribute( 'related_contentobject_array' );

            foreach( $relatedObjects as $object )
            {
                $result = array_merge( $result, eZIdmlExporter::getImagesPathFromContentObject( $object ) );
            }

            if ( $children )
            {
                $children = $node->attribute( 'children' );
                $result = array_merge( $result, eZIdmlExporter::getImagesFromNodes( $children ) );
            }
        }
        return $result;
    }
    
    static function getImagesPathFromContentObject( $object )
    {
        $result = array();

        foreach( $object->contentObjectAttributes() as $attribute )
        {
            if ( $attribute->attribute( 'data_type_string' ) == 'ezimage' )
            {
                $image = $attribute->content()->attribute('original');
                if ( $image['is_valid'] )
                    $result[] = $image['full_path'];
            }
        }

        return $result;
    }
    
    public static function addTplData( eZIdmlContent $idmlContent, eZContentObjectAttribute $idmlContentObjectAttribute, $asXML = false )
    {
        
        eZLog::write( 'Process ' . $idmlContent->attribute( 'id' ) . ' - ' . $idmlContentObjectAttribute->attribute( 'data_type_string' ), eZIdmlExporter::LOGFILENAME  );

        $tpl = eZTemplate::factory();

        $res = eZTemplateDesignResource::instance();
        $res->setKeys( array( array( 'layout', 'idml' ) ) );       
        
        if ( $idmlContent->hasAttribute( 'handler' ) )
        {
            $attributeHandler = $idmlContent->attribute( 'handler' );
            eZLog::write( 'Use handler ' . $attributeHandler, eZIdmlExporter::LOGFILENAME  );
            $asXML = call_user_func( array( $attributeHandler, 'fetchTemplateAsXML' ), $idmlContent, $idmlContentObjectAttribute );
            $tpl = call_user_func( array( $attributeHandler, 'tplSetVariable' ), $idmlContent, $idmlContentObjectAttribute, $tpl );
            $designPath = call_user_func( array( $attributeHandler, 'getDesignPath' ), $idmlContent, $idmlContentObjectAttribute, $tpl );
        }
        elseif ( !$idmlContent->hasAttribute( 'xmltag' ) && $idmlContentObjectAttribute->attribute( 'data_type_string' ) == 'ezxmltext' )
        {
            $attributeHandler = 'eZIdmlCustomEzXmlText';
            eZLog::write( 'Use handler as default for ezxmltext ' . $attributeHandler, eZIdmlExporter::LOGFILENAME  );
            $asXML = call_user_func( array( $attributeHandler, 'fetchTemplateAsXML' ), $idmlContent, $idmlContentObjectAttribute );
            $tpl = call_user_func( array( $attributeHandler, 'tplSetVariable' ), $idmlContent, $idmlContentObjectAttribute, $tpl );
            $designPath = call_user_func( array( $attributeHandler, 'getDesignPath' ), $idmlContent, $idmlContentObjectAttribute, $tpl ); 
        }
        else
        {
            if ( $idmlContent->hasAttribute( 'xmltag' ) )
            {
                $contentIni = eZINI::instance( 'content.ini' );
                if ( !in_array( $idmlContent->attribute( 'xmltag' ), $contentIni->variable( 'CustomTagSettings', 'AvailableCustomTags' ) ) )
                {
                    $tpl->setVariable( "classification", false );
                    $tpl->setVariable( "content", '' );
                    $designPath = "design:content/datatype/view/ezxmltags/literal.tpl";
                }
                else
                {
                    $tmpDom = new DOMDocument;
                    $content = '';
        
                    if ( $tmpDom->loadXML( $idmlContentObjectAttribute->attribute( 'data_text' ) ) )
                    {
                        $eZTagName = 'custom[@name="' . $idmlContent->attribute( 'xmltag' ) . '"]';
                        $tmpXpath = new DOMXPath( $tmpDom );
                        $tmpNodeList = $tmpXpath->query( 'descendant::' . $eZTagName );
                        
                        $xmlTagPriority =  (int) $idmlContent->attribute( 'xmltag_priority' ) - 1;    
    
                        if ( !$tmpNodeList->length )
                        {
                            eZLog::write( 'WARNING! ' . $idmlContent->attribute( 'xmltag' ) . ' not found in attribute ' . $idmlContentObjectAttribute->attribute( 'contentclass_attribute_identifier' ) . ' of node #' . $idmlContent->attribute( 'eZContentObjectTreeNodeID' ) , eZIdmlExporter::LOGFILENAME );
                            $content = '';
                        }
                        else
                        {
                            if ( $tmpNodeList->length >= $idmlContent->attribute( 'xmltag_priority' ) )
                            {
                                $content = $tmpNodeList->item( $xmlTagPriority )->nodeValue;
                                //@TODO collect the attributes!
                            }
                        }
    
                    }
                    eZLog::write( $content , eZIdmlExporter::LOGFILENAME );
                    //@TODO set attributes
                    $tpl->setVariable( "content", $content );
                    $designPath = "design:content/datatype/view/ezxmltags/" . $idmlContent->attribute( 'xmltag' ) . ".tpl";
                }
            }
            else
            {
                $tpl->setVariable( "attribute", $idmlContentObjectAttribute );
                $designPath = "design:content/datatype/view/idml/" . $idmlContentObjectAttribute->attribute( 'data_type_string' ) . ".tpl";
            }
        }

        if ( $asXML )
        {
            $dom = new DOMDocument;
            eZLog::write( 'Fetch template ' . $designPath, eZIdmlExporter::LOGFILENAME );
            $tplFetch = $tpl->fetch( $designPath );
            $tplFetch = str_replace( '&nbsp;', ' ', $tplFetch );  //@TODO other check...
            
            //eZDebug::writeNotice( 'Contenuto del FetchTemplate di ' . $idmlContent->attribute( 'tag' ) . ': ' . $tplFetch, __METHOD__ );
            
            if ( $dom->loadXML( '<element>' . $tplFetch . '</element>') )
            {
                $xpath = new DOMXPath( $dom );
                $nodeList = $xpath->query(  '/element/*' );
                
                if ( $idmlContent->hasAttribute( 'handler' ) || $idmlContentObjectAttribute->attribute( 'data_type_string' ) == 'ezxmltext' )
                {
                    $attributeHandler = false;
                    
                    if ( $idmlContentObjectAttribute->attribute( 'data_type_string' ) == 'ezxmltext' && !$idmlContent->hasAttribute( 'xmltag' ) )
                    {
                        $attributeHandler = 'eZIdmlCustomEzXmlText';
                    }
                    else
                    {
                        $attributeHandler = $idmlContent->attribute( 'handler' );
                    }
                    
                    if ( $attributeHandler )
                    {
                        $nodeList = call_user_func( array( $attributeHandler, 'modifyNodeList' ), $idmlContent, $idmlContentObjectAttribute, $nodeList, $dom, $xpath );
                    }
                }
                    
                return $nodeList;
            }
            else
            {
                eZLog::write( 'XML Error in ' . $designPath . ': ' . $tpl->fetch( $designPath ) , eZIdmlExporter::LOGFILENAME );
            }
        }
        else
        {
            //eZDebug::writeNotice( 'Contenuto del FetchTemplate di ' . $idmlContent->attribute( 'tag' ) . ': ' . $tpl->fetch( $designPath ), __METHOD__ );
            eZLog::write( 'Fetch template ' . $designPath, eZIdmlExporter::LOGFILENAME );
            return $tpl->fetch( $designPath );
        }
        return '';
    }    
}

?>
