<?php
/**
 * File containing the svg files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$Module = $Params["Module"];
$http = eZHTTPTool::instance();

$NodeID = 0;
if ( isset( $Params['NodeID'] ) )
{
    $NodeID = $Params['NodeID'];
}

if ( strpos( $NodeID, '::' ) !== false )
{
    $parts = explode( '::', $NodeID );
    if ( count( $parts ) == 3 )
    {
        $SpreadID = $parts[0];
        $attributeID = $parts[1];
        $attributeVersion = $parts[2];
        
        $ContentObjectAttribute = eZContentObjectAttribute::fetch( $attributeID, $attributeVersion );
        
        if ( $ContentObjectAttribute && $ContentObjectAttribute->attribute( 'data_type_string' ) == 'ezidml' )
        {
            $idmlPackageHandler = new eZIdmlPackageHandler( $ContentObjectAttribute );

            foreach( $idmlPackageHandler->idml->attribute( 'svg_files' ) as $id => $filePath )
            {
                if ( $id == $SpreadID )
                {
                    $svgFile = eZClusterFileHandler::instance( $filePath );
                    if ( $svgFile->exists() )
                    {
                        header( 'Content-Type: text/xml' );
                        echo file_get_contents( $filePath );
                        eZExecution::cleanExit();
                    }
                    else
                    {
                        echo 'no file found';
                        eZExecution::cleanExit();
                    }
                }
            }
        }
    }
}

$Scale = '0.5';
if ( isset( $Params['Scale'] ) )
{
    $Scale = $Params['Scale'];
}

$SpreadID = false;
if ( isset( $Params['SpreadID'] ) )
{
    $SpreadID = $Params['SpreadID'];
}


$node = eZContentObjectTreeNode::fetch( $NodeID );

if ( $node )
{

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
    if ( $ContentObjectAttribute )
    {
        $idmlPackageHandler = new eZIdmlPackageHandler( $ContentObjectAttribute );
        
        foreach( $idmlPackageHandler->idml->attribute( 'spreads' ) as $id => $spread )
        {
            if ( $id == $SpreadID )
            {
                $svg = new eZIdmlSvg( $spread, $idmlPackageHandler->idml, array( 'scale' => $Scale ) );                
                header( 'Content-Type: text/xml' );
                echo $svg->output();
            }
        }        
    }

}

eZExecution::cleanExit();

?>
