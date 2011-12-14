<?php
/**
 * File containing the export idml files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$Module = $Params["Module"];
$http = eZHTTPTool::instance();


if ( isset( $Params['NodeID'] ) )
{
    $nodeID = $Params['NodeID'];
}
elseif ( $http->hasPostVariable( 'NodeID' ) && $http->hasPostVariable( 'ExportAction' ) )
{
    $nodeID = $http->postVariable( 'NodeID' );
}

if ( isset( $Params['ExportType'] ) )
{
    $exportType = $Params['ExportType'];
}
else
{
    if ( $http->hasPostVariable( 'ExportIdml' )  )
    {
        $exportType = 'idml';
    }
    elseif ( $http->hasPostVariable( 'ExportImages' )  )
    {
        $exportType = 'images';
    }
    elseif ( $http->hasPostVariable( 'ExportDebug' )  )
    {
        $exportType = '_debug';
    }
    elseif ( $http->hasPostVariable( 'ExportSimpleDebug' )  )
    {
        $exportType = '_simpledebug';
    }
}

if ( isset( $Params['LanguageCode'] ) )
{
    $languageCode = $Params['LanguageCode'];
}
elseif ( $http->hasPostVariable( 'LanguageCode' )  )
{
    $languageCode = $http->postVariable( 'LanguageCode' );
}
else
{
    $locale = eZLocale::instance();
    $languageCode = $locale->localeCode();
}


if ( !$nodeID )
    return $Module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );

$node = eZContentObjectTreeNode::fetch( $nodeID, $languageCode );

if ( !$node )
    return $Module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );


$exporter = new eZIdmlExporter( $node, $languageCode );

if ( $exportType == '_simpledebug' )
{
    $debugIdml = $exporter->debugIdml();
    echo '<pre>';
    print_r( $debugIdml );
    eZDisplayDebug();
    eZExecution::cleanExit();
}

$exportFiles = $exporter->execute();

if ( $exportType == '_debug' )
{
    echo '<pre>';
    print_r( $exportFiles );
    eZDisplayDebug();
    eZExecution::cleanExit();
}

$file = eZClusterFileHandler::instance( $exportFiles['files'][0][$exportType] );

if ( isset( $exportFiles['files'][0][$exportType] ) && $file->exists() )
{
    eZFile::download( $exportFiles['files'][0][$exportType], true, basename( $exportFiles['files'][0][$exportType] ) );
}
else
{
    return $Module->handleError( 'notfound', 'kernel' ); 
}


eZDisplayDebug();
eZExecution::cleanExit();

?>
