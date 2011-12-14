<?php
/**
 * File containing the modules files.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$Module = array( 'name' => 'OpenMagazine' );

$ViewList = array();
$ViewList['export_idml'] = array(
                                 'script' => 'export_idml.php',
                                 'functions' => array( 'export_idml' ),
                                 'params' => array( 'NodeID', 'ExportType','LanguageCode' )
                                 );

#$ViewList['update_from_idml'] = array(
#                                      'script' => 'update_from_idml.php',
#                                      'functions' => array( 'update' ),
#                                      'params' => array( 'NodeID' )
#                                      );

$ViewList['svg'] = array(
                         'script' => 'svg.php',
                         'functions' => array( 'svg' ),
                         'params' => array( 'NodeID', 'SpreadID', 'Scale' )
                         );

$ViewList['sort'] = array(
                          'functions' => array( 'sort' ),
                          'script' => 'sort.php',
                          'params' => array( 'NodeID' ),
                          'unordered_params' => array( 'language' => 'Language',
                                                        'redirect' => 'RedirectURL',
                                                        'offset' => 'Offset',
                                                        'year' => 'Year',
                                                        'month' => 'Month',
                                                        'day' => 'Day' )
                          );

$ViewList['export_xml'] = array(
                                'functions' => array( 'export_xml' ),
                                'script' => 'export_xml.php',
                                'post_actions' => array( 'BrowseActionName' ),
                                'default_navigation_part' => 'ezopenmagazinenavigationpart'
                                );

$ViewList['do_export_xml'] = array(
                                   'functions' => array( 'export_xml' ),
                                   'script' => 'do_export_xml.php',
                                   'params' => array( 'NodeID' ),
                                   'default_navigation_part' => 'ezopenmagazinenavigationpart'
                                   );

#$ViewList['dashboard'] = array(
#                               'functions' => array( 'dashboard' ),
#                               'script' => 'dashboard.php',
#                               'functions' => array( 'dashboard' ),
#                               'default_navigation_part' => 'ezopenmagazinenavigationpart'
#                               );

$ViewList['list'] = array(
                          'script' => 'list.php',
                          'functions' => array( 'export_idml' ),
                          'params' => array( 'NodeID', 'ExportView', 'ContentType' )
                          );

$ViewList['test'] = array('script' => 'test.php', 'params' => array( 'NodeID', 'Attribute' ));

$FunctionList = array();
#$FunctionList['dashboard'] = array();
#$FunctionList['update'] = array();
$FunctionList['export_idml'] = array();
$FunctionList['export_xml'] = array();
$FunctionList['svg'] = array();
$FunctionList['sort'] = array( 'Class' => array( 'name'=> 'Class',
                                                'values'=> array(),
                                                'path' => 'classes/',
                                                'file' => 'ezcontentclass.php',
                                                'class' => 'eZContentClass',
                                                'function' => 'fetchList',
                                                'parameter' => array( 0, false, false, array( 'name' => 'asc' ) ) ) );

?>
