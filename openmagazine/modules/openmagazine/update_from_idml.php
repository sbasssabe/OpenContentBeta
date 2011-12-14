<?php
/**
 * File containing the update from xml files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$Module = $Params["Module"];
$http = eZHTTPTool::instance();
$idmlIni = eZINI::instance( 'idml.ini' );

if ( isset( $Params['NodeID'] ) )
{
    $nodeID = $Params['NodeID'];
}
elseif ( $http->hasPostVariable( 'NodeID' ) && $http->hasPostVariable( 'ExportAction' ) )
{
    $nodeID = $http->postVariable( 'NodeID' );
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

$node = eZContentObjectTreeNode::fetch( $nodeID );

if ( !$node )
    return $Module->handleError( eZError::KERNEL_NOT_AVAILABLE, 'kernel' );

echo '@TODO';

eZDisplayDebug();
eZExecution::cleanExit();

?>
