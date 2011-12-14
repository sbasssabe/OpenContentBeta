<?php
/**
 * File containing the eZIdmlUploadHandler class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlUploadHandler extends eZContentUploadHandler
{
    function eZIdmlUploadHandler()
    {
        $this->eZContentUploadHandler( 'Idml file handling', 'adobe indesign idml' );
    }

    function handleFile( &$upload, &$result,
                         $filePath, $originalFilename, $mimeinfo,
                         $location, $existingNode )
    {
        
        $tmpDir = getcwd() . "/" . eZSys::cacheDirectory();
        $originalFilename = basename( $originalFilename );
        $tmpFile = $tmpDir . "/" . $originalFilename;
        copy( $filePath, $tmpFile );
        
        $params = array();
        
        $idmlINI = eZINI::instance( 'idml.ini' );
        if ( $idmlINI->hasVariable( 'FileImport', 'CreateContentOnUpload' ) )
        {
            $params['create_or_upload'] = true;
        }
        if ( $idmlINI->hasVariable( 'FileImport', 'SetSourceNodeOnUpload' ) )
        {
            if ( intval( $idmlINI->variable( 'FileImport', 'SetSourceNodeOnUpload' ) ) > 0 )
            $params['source_node_id'] = intval( $idmlINI->variable( 'FileImport', 'SetSourceNodeOnUpload' ) );
        }
                
        $import = new eZIdmlFileImport();        
        $tmpResult = $import->import( $tmpFile, $location, $originalFilename, $upload, $params );

        $result['contentobject'] = $tmpResult['Object'];
        $result['contentobject_main_node'] = $tmpResult['MainNode'];
        unlink( $tmpFile );

        return true;
    }

}
?>
