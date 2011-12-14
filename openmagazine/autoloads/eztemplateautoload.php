<?php
/**
 * File containing the eZTemplateOperatorArray array.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

$eZTemplateOperatorArray = array(
    array(
        'script' => 'extension/openmagazine/autoloads/openmagazinetploperators.php',
        'class' => 'OpenMagazineTplOperators',
        'operator_names' => array(
                                'washxml',
                                'washxmlcomment',
                                'washxmlcdata',
                                'httpcharset',
                                'httpheader',
                                'check_char_length'
                                )
    )
);

$eZTemplateOperatorArray[] = array( 'script' => 'extension/openmagazine/autoloads/openmagazinexpressstyleoperator.php',
                                    'class' => 'OpenMagazineXpressStyleOperator',
                                    'operator_names' => array( 'xpress_style', 'xpress_style_name' ) );

?>
