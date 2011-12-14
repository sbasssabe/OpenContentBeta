<?php
/**
 * File containing the eZIdmlXMLOutput class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlXMLOutput extends eZXMLOutputHandler
{

    public $debug;
    public $OutputTags = array(

    'embed'        => array( 'initHandler' => 'initHandlerEmbed',
                             'renderHandler' => 'renderText',
                             'attrNamesTemplate' => array( 'class' => 'classification',
                                                           'xhtml:id' => 'id',
                                                           'object_id' => false,
                                                           'node_id' => false,
                                                           'show_path' => false ),
                             'attrDesignKeys' => array( 'class' => 'classification' ) ),

    'embed-inline' => array( 'initHandler' => 'initHandlerEmbed',
                             'renderHandler' => 'renderText',
                             'attrNamesTemplate' => array( 'class' => 'classification',
                                                           'xhtml:id' => 'id',
                                                           'object_id' => false,
                                                           'node_id' => false,
                                                           'show_path' => false ),
                             'attrDesignKeys' => array( 'class' => 'classification' ) ),

    '#text'        => array( 'quickRender' => true,
                             'renderHandler' => 'renderText' )    
    );

    function eZIdmlXMLOutput( &$xmlData, $aliasedType, $contentObjectAttribute = null )
    {
        $this->eZXMLOutputHandler( $xmlData, $aliasedType, $contentObjectAttribute );

        $ini = eZINI::instance('ezxml.ini');
        $this->RenderParagraphInTableCells = false;
        
        $this->debug = false;
    }

    function initHandlerEmbed( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        // default return value in case of errors
        $ret = array( 'no_render' => true );

        $tplSuffix = '';
        $objectID = $element->getAttribute( 'object_id' );
        if ( $objectID &&
             !empty( $this->ObjectArray["$objectID"] ) )
        {
            $object = $this->ObjectArray["$objectID"];
        }
        else
        {
            $nodeID = $element->getAttribute( 'node_id' );
            if ( $nodeID )
            {
                if ( isset( $this->NodeArray[$nodeID] ) )
                {
                    $node = $this->NodeArray[$nodeID];
                    $objectID = $node->attribute( 'contentobject_id' );
                    $object = $node->object();
                    $tplSuffix = '_node';
                }
                else
                {
                    eZDebug::writeWarning( "Node #$nodeID doesn't exist", "XML output handler: embed" );
                    return $ret;
                }
            }
        }

        if ( !isset( $object ) || !$object || !( $object instanceof eZContentObject ) )
        {
            eZDebug::writeWarning( "Can't fetch object #$objectID", "XML output handler: embed" );
            return $ret;
        }
        if ( $object->attribute( 'status' ) != eZContentObject::STATUS_PUBLISHED )
        {
            eZDebug::writeWarning( "Object #$objectID is not published", "XML output handler: embed" );
            return $ret;
        }

        if ( $object->attribute( 'can_read' ) ||
             $object->attribute( 'can_view_embed' ) )
        {
            $templateName = $element->nodeName . $tplSuffix;
        }
        else
        {
            $templateName = $element->nodeName . '_denied';
        }

        $objectParameters = array();
        $excludeAttrs = array( 'view', 'class', 'node_id', 'object_id' );

        foreach ( $attributes as $attrName => $value )
        {
           if ( !in_array( $attrName, $excludeAttrs ) )
           {
               if ( strpos( $attrName, ':' ) !== false )
                   $attrName = substr( $attrName, strpos( $attrName, ':' ) + 1 );

               $objectParameters[$attrName] = $value;
               unset( $attributes[$attrName] );
           }
        }

        if ( isset( $parentParams['link_parameters'] ) )
            $linkParameters = $parentParams['link_parameters'];
        else
            $linkParameters = array();


        $ret = array( 'template_name' => $templateName,
                      'tpl_vars' => array( 'object' => $object,
                                           'link_parameters' => $linkParameters,
                                           'object_parameters' => $objectParameters ),
                      'design_keys' => array( 'class_identifier' => $object->attribute( 'class_identifier' ) ) );

        if ( $tplSuffix == '_node')
            $ret['tpl_vars']['node'] = $node;

        return $ret;
    }

    function initHandlerTable( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        // Backing up the section_level, headings' level should be restarted inside tables.
        // @see http://issues.ez.no/11536
        $this->SectionLevelStack[] = $parentParams['section_level'];
        $parentParams['section_level'] = 0;

        // Numbers of rows and cols are lower by 1 for back-compatibility.
        $rowCount = self::childTagCount( $element ) -1;
        $lastRow = $element->lastChild;

        while ( $lastRow && !( $lastRow instanceof DOMElement && $lastRow->nodeName == 'tr' ) )
        {
           $lastRow = $lastRow->previousSibling;
        }

        $colCount = self::childTagCount( $lastRow );

        if ( $colCount )
            $colCount--;

        $ret = array( 'tpl_vars' => array( 'col_count' => $colCount,
                                           'row_count' => $rowCount ) );
        return $ret;
    }

    function leavingHandlerTable( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        // Restoring the section_level as it was before entering the table.
        // @see http://issues.ez.no/11536
        $parentParams['section_level'] = array_pop($this->SectionLevelStack);
    }

    function initHandlerTr( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        $ret = array();
        if( !isset( $siblingParams['table_row_count'] ) )
            $siblingParams['table_row_count'] = 0;
        else
            $siblingParams['table_row_count']++;

        $parentParams['table_row_count'] = $siblingParams['table_row_count'];

        // Number of cols is lower by 1 for back-compatibility.
        $colCount = self::childTagCount( $element );
        if ( $colCount )
            $colCount--;

        $ret = array( 'tpl_vars' => array( 'row_count' => $parentParams['table_row_count'],
                                           'col_count' => $colCount ) );

        // Allow overrides based on table class
        $parent = $element->parentNode;
        if ( $parent instanceof DOMElement && $parent->hasAttribute('class') )
            $ret['design_keys'] = array( 'table_classification' => $parent->getAttribute('class') );

        return $ret;
    }

    function initHandlerTd( $element, &$attributes, &$siblingParams, &$parentParams )
    {
        if( !isset( $siblingParams['table_col_count'] ) )
            $siblingParams['table_col_count'] = 0;
        else
            $siblingParams['table_col_count']++;

        $ret = array( 'tpl_vars' => array( 'col_count' => &$siblingParams['table_col_count'],
                                           'row_count' => &$parentParams['table_row_count'] ) );

        // Allow overrides based on table class
        $parent = $element->parentNode->parentNode;
        if ( $parent instanceof DOMElement && $parent->hasAttribute('class') )
            $ret['design_keys'] = array( 'table_classification' => $parent->getAttribute('class') );

        return $ret;
    }


    function renderParagraph( $element, $childrenOutput, $vars )
    {
        return;
    }
    
    function renderAll( $element, $childrenOutput, $vars )
    {
        return;
    }

    
    static function getNodeXPath( $node )
    {   
        $result='';
        while ($parentNode = $node->parentNode) {
            $nodeIndex = -1;
            $nodeTagIndex = 0;
            do {
                $nodeIndex++;
                $testNode = $parentNode->childNodes->item( $nodeIndex );

                if ( $testNode->nodeName == $node->nodeName and $testNode->parentNode->isSameNode( $node->parentNode ) and !empty( $testNode->childNodes ) )
                {
                    //echo "{$testNode->parentNode->nodeName}-{$testNode->nodeName}-{}<br/>";
                    $nodeTagIndex++;
                }
                   
            }
            while ( !$node->isSameNode( $testNode ) );
            
            $attributes = $node->attributes;
            $attr = '';
            if ( $attributes !== NULL && count( $attributes ) )
            {
                foreach ( $attributes as $a )
                {
                    if ( !empty( $attr ) )
                        $attr .= '::';
                    
                    $attr .= $a->name . '=' . $a->value;
                }
                
                if ( !empty( $attr ) )
                    $attr = '&' . $attr;
            }
            
            if ( $node->nodeName == 'section' )
                $result = "{$node->nodeName}$attr/" . $result;
            else
                $result = "{$node->nodeName}-{$nodeTagIndex}$attr/" . $result;
            
            $node = $parentNode;
        };        
        
        return $result;        
    }

    function renderText( $element, $childrenOutput, $vars )
    {
        if ( $element->parentNode->nodeName != 'literal' )
        {
            $text = htmlspecialchars( $element->textContent );
            $text = str_replace ( '&amp;nbsp;', '&nbsp;', $text);
            // Get rid of linebreak and spaces stored in xml file
            $text = str_replace( "\n", '', $text );

            //if ( $this->AllowMultipleSpaces )
            //    $text = str_replace( '  ', ' &nbsp;', $text );
            //else
            //    $text = preg_replace( "# +#", " ", $text );

            if ( $this->AllowNumericEntities )
                $text = preg_replace( '/&amp;#([0-9]+);/', '&#\1;', $text );
        }
        else
        {
            $text = $element->textContent;
        }
        
        $xpath =  self::getNodeXPath( $element );
        
        return $this->renderPart( array( $xpath, '<Content>'.$text.'</Content>' ) );
    }
    
    public $section;
    
    public $characterStyles = array();
    
    public $paragraph = array();
    public $li = array();
    public $openParagraph = null;
    public $closeParagraph = false;
    public $paragraphIsOpen = false;
    public $ulOl = array();
    public $openUlOl = false;
    public $closeUlOl = false;
    public $ulOlList = false;
    public $olList = false;
    public $ulList = false;
    public $ulOlIsOpen = false;
    
    public $table = array();
    public $inTable = false;
    public $currentTr = array();
    public $currentTd = array();
    public $currentLi = false;
    public $addBr = false;
    
    public $output = '';
    
    function renderPart( $array )
    {

        $this->section = 0;
        $this->currentTable = false;
        $this->currentTr = false;
        $this->currentTd = false;
        $this->characterStyles = array();
        $this->ulOlList = false;
        $this->olList = false;
        $this->ulList = false;
        $this->addBr = false;
        $this->inTable = false;
        
        $text = $array[1];
        $result = explode( '/', $array[0] );
        array_shift( $result );

        if ( $this->debug )
        {
            echo '++++++++++' ."\n";
            echo $text."\n";
            echo '++++++++++'."\n";
            print_r($result);
        }

        foreach( $result as $r )
        {
            if ( $r == 'section' )
            {
                $this->section++;
            }
            else
            {
                $r = explode( '&', $r );

                $attributes = array();
                if ( isset( $r[1] ) )
                {
                    $_attributes = explode( '::', $r[1] );
                    foreach( $_attributes as $a )
                    {
                        $a = explode( '=', $a );
                        $attributes[$a[0]] = $a[1];
                    }
                      
                }

                $tagCount = explode( '-', $r[0] );

                $tag = $tagCount[0];
                if ( $tag == 'custom' )
                    $tag = $attributes['name'];

                $count = isset( $tagCount[1] ) ? $tagCount[1] : 0;
                $writeText = true;
                
                switch( $tag )
                {
                    case 'paragraph':
                        
                        if ( empty( $this->table ) && !( $this->inTable ) && !( $this->ulOlList ) )
                        {
                        
                            if ( empty( $this->paragraph ) )
                            {
                                $this->openParagraph = true;
                                $this->closeParagraph = false;
                            }
                            elseif ( $this->paragraph['id'] != $count )
                            {
                                $this->openParagraph = true;
                                $this->closeParagraph = true;
                            }
                            elseif ( $this->paragraph['id'] == $count )
                            {
                                $this->openParagraph = false;
                                $this->closeParagraph = false;
                            }
                            $this->paragraph['id'] = $count;
                            
                            if ( is_array( $attributes ) )
                            {
                                if ( isset( $attributes['class'] ) )
                                {
                                    $this->paragraph['style'] = $this->normalizeParagraphStyle( $attributes['class'] );
                                }
                                else
                                {
                                    $this->paragraph['style'] = $this->normalizeParagraphStyle( 'default' );
                                }
                            }
                            else
                            {
                                $this->paragraph['style'] = $this->normalizeParagraphStyle( 'default' );
                            }
                        }
                        
                        break;
                    
                    case 'header':
                        
                        if ( $this->paragraph['id'] !== $tag.$this->section )
                        {
                            $this->closeParagraph = true;
                            $this->openParagraph = true;
                        }
                        else
                        {
                            $this->closeParagraph = false;
                            $this->openParagraph = false;
                        }
                        $this->paragraph['id'] = $tag.$this->section;
                        $this->paragraph['style'] = $this->normalizeParagraphStyle( 'Header' . $this->section  );
                        
                        break;
                    
                    case 'table':
                        
                        if ( !isset( $this->table[$count] ) )
                        {  
                            $this->table[$count] = array();
                            $this->closeParagraph = true;
                        }
                        else
                        {
                            $this->closeParagraph = false;
                            $this->openParagraph = false;
                        }
                        $this->currentTable = $count;
                        
                        break;
                    
                    case 'tr':

                        if ( !isset( $this->table[$this->currentTable]['tr'] ) )
                        {
                            $this->table[$this->currentTable]['tr'] = array();
                            $this->table[$this->currentTable]['tr']['count'] = 1;
                        }
                        
                        $this->currentTr = $count;
                        
                        if ( $count > $this->table[$this->currentTable]['tr']['count'] )
                            $this->table[$this->currentTable]['tr']['count'] = $count;
                        
                        break;
                    
                    case 'td':

                        $this->inTable = true;
                        
                        if ( !isset( $this->table[$this->currentTable]['td'] ) )
                        {
                            $this->table[$this->currentTable]['td']['count']  = array();
                            $this->table[$this->currentTable]['td']['count']  = 1;
                        }

                        $this->currentTd = $count;

                        if ( $count > $this->table[$this->currentTable]['td']['count'] )
                            $this->table[$this->currentTable]['td']['count'] = $count;                        
                        
                        $cell = array(
                            'tr' => $this->currentTr,
                            'td' => $this->currentTd,
                            'text' => '',
                            'style' => array()
                        );
                        
                        if ( is_array( $attributes ) )
                        {
                            if ( isset( $attributes['colspan'] ) )
                            {
                                $cell['colspan'] = $attributes['colspan'];
                            }
                            if ( isset( $attributes['rowspan'] ) )
                            {
                                $cell['rowspan'] = $attributes['rowspan'];
                            }
                        }
                        
                        $key = $this->currentTr . ' - ' . $this->currentTd;
                        
                        if ( !isset( $this->table[$this->currentTable]['cell'] ) )
                        {
                            $this->table[$this->currentTable]['cell'] = array( $key => $cell );
                        }
                        else
                        {
                             if ( isset( $this->table[$this->currentTable]['cell'][$key]['text'] ) )
                             {
                                $cell['text'] = $this->table[$this->currentTable]['cell'][$key]['text'];
                             }
                             $this->table[$this->currentTable]['cell'][$key] = $cell;
                        }
                        
                        break;
                    
                    case 'ul':
                    case 'ol':
                        
                        $this->ulOlList = true;
                        if ( $tag == 'ol' )
                        {
                            $this->olList = true;
                        }
                        if ( $tag == 'ul' )
                        {
                            $this->ulList = true;
                        }
                        if ( empty( $this->ulOl ) )
                        {
                            $this->openUlOl = true;
                            $this->closeUlOl = false;
                        }
                        elseif ( $this->ulOl['id'] != $count )
                        {
                            $this->openUlOl = true;
                            $this->closeUlOl = true;
                        }
                        elseif ( $this->ulOl['id'] == $count )
                        {
                            $this->openUlOl = false;
                            $this->closeUlOl = false;
                        }
                        $this->ulOl['id'] = $count;
                        
                        break;
                    
                    case 'line':
                        
                        //$this->addBr = true;
                        
                        break;
                    
                    case 'li':
                        
                        $this->currentLi = $count;
                        
                        break;
                    
                    case 'embed':
                        //@TODO
                        $text = '';
                        break;
                    
                    case 'factbox':
                        $this->openParagraph = true;
                        $this->closeParagraph = true;
                        break;

                    case 'emphasize':
                    case 'strong':
                        
                        $this->characterStyles[] = $tag;
                        
                        break;
                    
                    default:
                        
                        if ( isset( $this->table[$this->currentTable]['cell'] ) )
                        {
                            $key = $this->currentTr . ' - ' . $this->currentTd;
                            $this->table[$this->currentTable]['cell'][$key]['style'][] = $tag;
                        }
                        
                        
                        //$this->characterStyles[] = $tag;
                        
                    break;
                }
                
            }
        }
        
        if ( $this->currentTable )
        {
            $writeText = false;
        }
        
        $this->output .= $this->writeText( $text, $writeText );

        if ( $this->debug )
        {
            print_r( htmlentities( $this->output ) );
        }


    }
    
    function normalizeParagraphStyle( $styles )
    {
        $defaults = array( 'default' );
        $style = 'NormalParagraphStyle';
        if ( $styles != 'default' )
        {
            $styles = str_replace( 'default', '', $styles);
            trim( $styles );
            $styles = explode( ' ', $styles );
            
            foreach( $styles as $i => $s )
            {
                if ( in_array( $s, $defaults ) )
                    unset( $styles[$i] );
                $styles[$i] = ucfirst( trim( $styles[$i] ) );
            }
            
            $style = implode( '', $styles );
        }
        if ( empty( $style ) )
            $style = 'NormalParagraphStyle';
            
        return $style;
    }
    
    function writeFontStyle( $array = false )
    {
        if ( !$array )
            $array = $this->characterStyles;
        
        if ( empty( $array ) )
        {
            return false;
        }
        
        if ( in_array( 'emphasize', $array ) && in_array( 'strong', $array ) )
        {
            return 'Bold Italic';
        }
        elseif ( in_array( 'emphasize', $array ) )
        {
            return 'Italic';
        }
        elseif ( in_array( 'strong', $array ) )
        {
            return 'Bold';       
        }
    }
    
    function writeCharacterStyle( $array = false )
    {
        $defaults = array( 'default', 'line' );
        
        if ( !$array )
            $array = $this->characterStyles;
        
        if ( empty( $array ) )
        {
            return '[No character style]';
        }
        else
        {
            sort( $array );
            $return = '';
            foreach( $array as $value )
            {
                if (  $value !== '#text' && !in_array( $value, $defaults ) )
                {
                    $value = str_replace( 'default', '', $value);
                    $value = trim( $value );
                    $value = ucfirst( $value );
                    $return .= $value;
                }
            }
            if ( !empty( $return ) )
                return $return;
        }
        return '[No character style]';
    }
    
    
    function writeText( $text = null, $writeText = true )
    {
        
        $output = '';
        if ( $writeText && !empty( $this->table ) )
        {
            foreach( $this->table as $id => $table )
            {
                $output .= $this->writeTable( $id );
            }
        } 
        
        if ( $this->ulOlIsOpen && !$this->ulOlList )
        {
            //$output .= '</CharacterStyleRange>' . "\n";
            $this->closeUlOl = true;
        }
        
        if ( $this->currentLi )
        {
            $this->li[] = $this->currentLi;
        }
        
        if ( $this->debug )
        {
            print_r( $this->li );
            var_dump( $this->currentLi );
            var_dump( $this->currentLi - 1 );
            var_dump( !$this->ulOlList && count( $this->li ) );
            var_dump( in_array( ( $this->currentLi - 1 ), $this->li ) );
        }
        
        if ( !$this->ulOlList && count( $this->li ) )
        {
            $output .= '<Br />' . "\n";
            $this->li  = array();
        }
        
        if ( $this->closeParagraph && $this->paragraphIsOpen && !$this->inTable )
        {
            $output .= '</ParagraphStyleRange>' . "\n";
            
            if ( !$this->closeUlOl )
                $output .= "<Br />\n";
            
            $this->paragraphIsOpen = false;
        }
        
        if ( $this->openParagraph && !$this->inTable )
        {
            $list = '';
            if ( $this->ulList )
            {
                $list = ' LeftIndent="18" FirstLineIndent="-18" BulletsAndNumberingListType="BulletList"';
            }
            if ( $this->olList )
            {
                $list = ' LeftIndent="18" FirstLineIndent="-18" BulletsAndNumberingListType="NumberedList" NumberingContinue="false"';
            }
                
            $output .= '<ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/' . $this->paragraph['style'] . '"' . $list . '>' . "\n";
            $this->paragraphIsOpen = true;
        }
    
        if ( $text !== null )
        {    
            $text = str_replace( '&nbsp;', ' ', $text );
            
            if ( $this->ulOlList && $this->openUlOl )
            {
                $this->ulOlIsOpen = true;
            }
            
            if ( count( $this->li ) )
            {
                if ( in_array( ($this->currentLi - 1), $this->li ) )
                {
                    $output .= '<Br />' . "\n";
                    $this->li = array( 0 => 100000 );
                }
            }

            $output .= '<CharacterStyleRange AppliedCharacterStyle="CharacterStyle/' . $this->writeCharacterStyle() . '" FontStyle="' . $this->writeFontStyle() . '">' . "\n";
            $output .= $text . "\n";
 
            if ( !count( $this->li ) || ( count( $this->li && !in_array( ($this->currentLi - 1), $this->li ) ) ))
            {
                $output .= '</CharacterStyleRange>' . "\n";
            }
        
            if ( $writeText )
            {
                return $output;
            }
            else
            {
                if ( $this->currentTable )
                {
                    if ( isset( $this->table[$this->currentTable]['cell'] ) )
                    {
                        $key = $this->currentTr . ' - ' . $this->currentTd;;
                        //$this->table[$this->currentTable]['cell'][$key]['text'] .= $output;
                        $this->table[$this->currentTable]['cell'][$key]['text'] .= $text;
                        return '';
                    }
                }
            }    
        
        }
    }
    
    function lastWrite()
    {
        if ( $this->paragraphIsOpen )
            $this->output .= '</ParagraphStyleRange>' . "\n";
        
        if ( !empty( $this->table ) )
        {
            foreach( $this->table as $i => $value )
            {
                $this->output .= $this->writeTable( $i );
            }
        }
    }
    
    function writeTable( $id )
    {
/*
foreach( $this->table[$id]['cell'] as $i => $cell )
{
   $this->table[$id]['cell'][$i]['text'] = htmlentities($this->table[$id]['cell'][$i]['text']); 
}
print_r($this->table[$id]);
*/
        $output = '';
        if ( isset( $this->table[$id] ) )
        {
            $tableID = 'genTab' . $id;
            $output .= '<Table Self="' . $tableID . '" HeaderRowCount="0" FooterRowCount="0" BodyRowCount="' . $this->table[$id]['tr']['count'] . '" ColumnCount="' . $this->table[$id]['td']['count'] . '" AppliedTableStyle="TableStyle/$ID/[No table style]" TableDirection="LeftToRightDirection" LeftBorderStrokeWeight="0.5" RightBorderStrokeWeight="0.5" TopBorderStrokeWeight="0.5" BottomBorderStrokeWeight="0.5" StartRowStrokeColor="Color/Black" StartRowStrokeCount="1" EndRowStrokeCount="1" StartColumnStrokeColor="Color/Black" StartColumnStrokeCount="1" EndColumnStrokeCount="1">' . "\n";
            
            for( $i = 0; $i < $this->table[$id]['tr']['count']; $i++ )
            {
                $output .= '<Row Self="' . $tableID . 'Row' . $i . '" Name="' . $i . '" SingleRowHeight="15.35" MinimumHeight="15.35" AutoGrow="true" />' . "\n";
            }
            
            for( $i = 0; $i < $this->table[$id]['td']['count']; $i++ )
            {
                $output .= '<Column Self="' . $tableID . 'Column' . $i . '" Name="' . $i . '" SingleColumnWidth="200.15" />' . "\n";
            }
            
            foreach( $this->table[$id]['cell'] as $cell )
            {
                $tr = $cell['tr'] - 1;
                $td = $cell['td'] - 1;
                $colspan = isset( $cell['colspan'] ) ? $cell['colspan'] : 1;
                $rowspan = isset( $cell['rowspan'] ) ? $cell['rowspan'] : 1;
                
                $output .= '<Cell Self="' . $tableID . 'i' . $cell['tr'].$cell['td'] . '" Name="' . $td . ':' . $tr. '" RowSpan="' . $rowspan . '" ColumnSpan="' . $colspan . '" AppliedCellStyle="CellStyle/$ID/[None]" AppliedCellStylePriority="0" LeftInset="5.4" TopInset="0" RightInset="5.4" BottomInset="0" FillColor="Color/Word_R243_G243_B243" FillTint="100" TopEdgeStrokeWeight="0.5" TopEdgeStrokeColor="Color/Black" TopEdgeStrokeType="StrokeStyle/$ID/Solid" FirstBaselineOffset="AscentOffset" VerticalJustification="CenterAlign" TopEdgeStrokeTint="100" TopEdgeStrokeOverprint="false" WritingDirection="true" TopEdgeStrokePriority="3" TopEdgeStrokeGapTint="100" TopEdgeStrokeGapOverprint="false">' . "\n";
                
                $output .= '<ParagraphStyleRange AppliedParagraphStyle="ParagraphStyle/$ID/NormalParagraphStyle">' . "\n";
                $output .= '<CharacterStyleRange AppliedCharacterStyle="CharacterStyle/$ID/' . $this->writeCharacterStyle( $cell['style'] ) . '">' . "\n";
                
                $cell['text'] = str_replace( '&nbsp;', ' ', $cell['text'] );                
                $output .= $cell['text'];
                
                $output .= '</CharacterStyleRange>' . "\n";
                $output .= '</ParagraphStyleRange>' . "\n";
                
                $output .= '</Cell>' . "\n";
            }
            $output .= '</Table>' . "\n";
            $this->paragraphIsOpen = false;
            $this->closeParagraph = false;
            unset( $this->table[$id] );
            return $output;
        }
    }

    function outputTag( $element, &$siblingParams, $parentParams = array() )
    {
        $output = parent::outputTag( $element, $siblingParams, $parentParams = array() );
        return array( false, $this->output );
    }
    
    function &outputText()
    {
        if ( !$this->XMLData )
        {
            $output = '';
            return $output;
        }

        $this->Tpl = eZTemplate::factory();
        $this->Res = eZTemplateDesignResource::instance();
        if ( $this->ContentObjectAttribute )
        {
            $this->Res->setKeys( array( array( 'attribute_identifier', $this->ContentObjectAttribute->attribute( 'contentclass_attribute_identifier' ) ) ) );
        }

        $this->Document = new DOMDocument( '1.0', 'utf-8' );
        $success = $this->Document->loadXML( $this->XMLData );

        if ( !$success )
        {
            $this->Output = '';
            return $this->Output;
        }

        $this->prefetch();

        $this->XMLSchema = eZXMLSchema::instance();

        // Add missing elements to the OutputTags array
        foreach( $this->XMLSchema->availableElements() as $element )
        {
            if ( !isset( $this->OutputTags[$element] ) )
            {
                 $this->OutputTags[$element] = array();
             }
        }

        $this->NestingLevel = 0;
        $params = array();

        $output = $this->outputTag( $this->Document->documentElement, $params );
        $this->output = $output[1];
        $this->lastWrite();
        
        $this->Output = $this->output;

        unset( $this->Document );

        $this->Res->removeKey( 'attribute_identifier' );
        return $this->Output;
    }
    
    public function setDebug( $bool )
    {
        $this->debug = $bool;
    }
    
}

?>
