<?php
/**
 * File containing the eZIdmlSvg class.
 *
 * @version //autogen//
 * @copyright //autogen//
 * @license //autogen//
 * @author //autogen//
 */

class eZIdmlSvg
{
    
    private $dom;
    private $params = array();
    public $pageWidth;
    public $pageHeight;
    public $width;
    public $height;
    public $defaultParams = array(
        'scale' => '0.5',
        'countPage' => '0'
    );
    public $countPage;
    
    function __construct( eZIdmlSpread $spread, eZIdmlDoc $idml, $params = array() )
    {
        
        $this->setParams( $params );
        $this->countPage = $this->params['countPage'];
        $this->idml = $idml;
        
        $this->dom = new DOMDocument( '1.0', 'utf-8' );
        $this->dom->formatOutput = true;
        $success = $this->dom->loadXML('<svg />');

        $docNode = $this->dom->documentElement;       
        $docNode->setAttribute( 'version', '1.1' );
        $docNode->setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
        $docNode->setAttribute( 'xmlns:xlink', 'http://www.w3.org/1999/xlink' );

        $node = $this->dom->createElement( 'g' );
        $node->setAttribute( 'transform', 'scale( '. $this->params['scale'] .')' );
        $docNode->appendChild( $node );
        foreach( $spread->attributes['pages'] as $page)
        {
            $pageTransformations = count( $page->attribute( 'itemTransform' ) ) > 0 ? $page->attribute( 'itemTransform' ) : array();
            //<rect x="0" y="0" width="595.275590551" height="841.889763778" style="fill:blue;stroke:pink;stroke-width:5; fill-opacity:0.1;stroke-opacity:0.9"/>
            $p = $this->dom->createElement( 'rect' );
            $p->setAttribute( 'id', $page->attribute( 'id' ) );
            $p->setAttribute( 'x', '0' );
            $p->setAttribute( 'y', '0' );
            $p->setAttribute( 'width', $page->attribute( 'width' ) );
            $this->pageWidth = $page->attribute( 'width' );
            $p->setAttribute( 'height', $page->attribute( 'height' ) );
            $this->pageHeight = $page->attribute( 'height' );

            $css_classes = array( 'page' );
            $p->setAttribute( 'class', implode( ' ', $css_classes ) );
            $p->setAttribute( 'style', 'fill:LightBlue;stroke:Black;stroke-width:1;fill-opacity:0.01;stroke-opacity:0.3' );
            
            $t = $this->applyTransformation( $pageTransformations, $node );
            if ( $this->countPage > 0 )
            {
                $this->renderPageNumber( $this->countPage, $p, $t );                
                $this->countPage++;
            }
            $t->appendChild( $p );
        }
        foreach( $spread->attributes['items'] as $item)
        {
            $i = $this->dom->createElement( $item->attribute( 'shape' ) );
            $i->setAttribute( 'id', $item->attribute( 'id' ) );
            
            $css_classes = array( $item->attribute( 'type' ), $item->attribute( 'shape' ) );
            
            switch( $item->attribute( 'shape' ) )
            {
                case 'polygon':
                case 'rectangle':
                case 'image':

                    if ( $item->hasAttribute( 'coordinates' ) )
                    {
                        $coordinates = $item->attribute( 'coordinates' );
                        $points = array();
                        foreach( $coordinates as $c )
                        {
                            $points[] = implode( ',', $c );
                        }
                        $i->setAttribute( 'points', implode( ' ', $points ) );
                    }
                    
                    if ( $item->hasAttribute( 'xy' ) )
                    {
                        $coordinates = $item->attribute( 'xy' );
                        $i->setAttribute( 'x', $coordinates[0] );
                        $i->setAttribute( 'y', $coordinates[1] );
                    }
                    
                    if ( $item->hasAttribute( 'width' ) )
                        $i->setAttribute( 'width', $item->attribute( 'width' ) );            
                    
                    if ( $item->hasAttribute( 'height' ) )
                        $i->setAttribute( 'height', $item->attribute( 'height' ) );

                    
                    break;
                case 'ellipse':

                    if ( $item->hasAttribute( 'coordinates' ) )
                    {
                        $coordinates = $item->attribute( 'coordinates' );
                        $i->setAttribute( 'cx', ( $coordinates[3][0] + ( ( $coordinates[1][0] - $coordinates[3][0] ) / 2 ) ) );
                        $i->setAttribute( 'cy', ( $coordinates[0][1] + ( ( $coordinates[2][1] - $coordinates[0][1] ) / 2 ) ) );
                        $i->setAttribute( 'rx', abs( ( $coordinates[1][0] - $coordinates[3][0] ) / 2 ) );
                        $i->setAttribute( 'ry', abs( ( $coordinates[2][1] - $coordinates[0][1] ) / 2 ) );
                    }

                    break;
                case 'line':
                    if ( $item->hasAttribute( 'coordinates' ) )
                    {
                        $coordinates = $item->attribute( 'coordinates' );
                        $i->setAttribute( 'x1', $coordinates[0][0] );
                        $i->setAttribute( 'y1', $coordinates[0][1] );
                        $i->setAttribute( 'x2', $coordinates[1][0] );
                        $i->setAttribute( 'y2', $coordinates[1][1] );
                    }
                    break;
                case 'path':
                    //@TODO
                    break;
                default:
                    continue;
                    break;
            }
            
            $itemTransformations = count( $item->attribute( 'itemTransform' ) ) > 0 ? $item->attribute( 'itemTransform' ) : array();

            /*
            if ( $item->hasAttribute( 'useAsMask' ) )
            {
                $mask = $this->dom->createElement( 'mask' );
                $mask->setAttribute( 'id', 'mask' . $item->attribute( 'id' ) );
                $node->appendChild( $mask );
                $t = $this->applyTransformation( $itemTransformations, $mask );
            }
            else
            {
                $t = $this->applyTransformation( $itemTransformations, $node );
            }
            */
            
            $t = $this->applyTransformation( $itemTransformations, $node );
            
            if ( $item->hasAttribute( 'ParentStory' ) )
            {
                $content = $this->idml->getContent( $item->attribute( 'ParentStory' ) );
                if ( $content && $this->params['scale'] > '0.4' )
                {
                    if ( $content->hasAttribute( 'tag' ) )
                    {
                        $css_classes[] = 'has_tag';
                        $css_classes[] = 'tag_' . $content->attribute( 'tag' );
                        $css_classes[] = 'story_' . $content->attribute( 'id' );
                    }
                }
            }
            
            if ( $item->hasAttribute( 'renderContent' ) && $this->params['scale'] > '0.4' )
            {
                $renderContent = $item->attribute( 'renderContent' );
                $css_classes[] = 'has_' . $renderContent['type'];
                $this->renderItemContent( $item, $i, $t );
            }
            
            $i->setAttribute( 'class', implode( ' ', $css_classes ) );
            if ( $item->hasAttribute( 'style' ) )
            {
                $style = array();
                foreach( $item->attribute( 'style' ) as $k => $s )
                {
                    $style[] = $k . ':'. $s;
                }
                $i->setAttribute( 'style', implode( ';', $style) );
            }
            
            $t->appendChild( $i );
        }
        $this->width = ( $this->params['scale'] * $this->pageWidth * 2 );
        $this->height = ( $this->params['scale'] * $this->pageHeight );
        $docNode->setAttribute( 'width', $this->width );
        $docNode->setAttribute( 'height', $this->height ); 
    }
    
    private function setParams( array $params )
    {        
        $this->params = array_merge( $this->defaultParams, $params );
    }
    
    private function applyTransformation( $transformations, DOMElement &$appendTo )
    {
        $defaultTransformation = "1 0 0 1 " . $this->pageWidth . " " . $this->pageHeight/2;
        
        if ( empty( $transformations ) )
            $transformations = array( array( 1,0,0,1,0,0 ) );
        else
            $transformations[0] = $defaultTransformation;
        
        //$transformations = array_reverse( $transformations );
        $g = array();
        foreach( $transformations as $i => $t )
        {
            $t = implode(',', explode( ' ', $t ) );
            $g[$i] = $this->dom->createElement( 'g' );
            $g[$i]->setAttribute( 'transform', 'matrix(' . $t . ')' );
            if ( $i == 0 )
                $appendTo->appendChild( $g[$i] );
            else
                $g[ ( $i-1 ) ]->appendChild( $g[$i] );
        }
        return $g[$i];
    }
    
    private function renderPageNumber( $number, DOMElement &$currentNode, DOMElement &$appendTo )
    {
        $text = $this->dom->createElement( 'text' );
        $numberLength = strlen( $number );
        switch( $numberLength )
        {
            case '1':
                $numberLength = $numberLength + 2;
                break;
            case '2':
                $numberLength = $numberLength + 4;
                break;
            default:
                $numberLength = $numberLength + 5;
            break;
        }
        
        $text->setAttribute( 'x', ( $this->pageWidth / $numberLength )  );
        $text->setAttribute( 'y', ( $this->pageHeight / 1.5 ) );
        $text->setAttribute( 'fill', 'LightBlue');
        $text->setAttribute( 'opacity', "0.4" );
        $text->setAttribute( 'style', 'font-family:Arial;font-size:400px;' );
        
        $textValue = $this->dom->createTextNode( $number );
        $text->appendChild( $textValue );

        $appendTo->appendChild( $text );
    }
    
    private function renderItemContent( $item, DOMElement &$currentNode, DOMElement &$appendTo )
    {
        $itemContent = $item->attribute( 'renderContent' );
        
        switch( $itemContent['type'] )
        {
            case 'image':
                
                $content = $this->idml->getContent( $item->attribute( 'id' ) );
                
                if ( $content )
                {
                    $image = $content->getImagePath();
                    $currentNode->setAttribute( 'id', $content->attribute( 'id' ) );
                    $currentNode->setAttribute( 'xlink:href', $image );
                    $currentNode->setAttribute( 'preserveAspectRatio', "xMidYMin slice" );
                    $currentNode->setAttribute( 'opacity', "0.3" );
                }
                
                break;
            
            case 'image_container':
            case 'text':
                                
                $text = $this->dom->createElement( 'text' );
               
                $content = $this->idml->getContent( $item->attribute( 'ParentStory' ) );
               
                if ( $itemContent['type'] == 'image_container' )
                    $content = $this->idml->getContent( $itemContent['imageID'] );

                if ( $content )
                {
                    $width = false;
                    if ( $item->hasAttribute( 'width' ) )
                        $width = $item->attribute( 'width' );
                    
                    if ( $item->hasAttribute( 'xy' ) )
                    {
                        $coordinates = $item->attribute( 'xy' );
                        $x = $coordinates[0];
                        $y =  $coordinates[1] + 20;
                    }
                    elseif ( $item->hasAttribute( 'coordinates' ) )
                    {
                        $pointParam = 0;
                        if ( $item->attribute( 'shape' ) == 'ellipse' )
                            $pointParam = 3;
                        $coordinates = $item->attribute( 'coordinates' );
                        $x = $coordinates[$pointParam][0];
                        $y =  $coordinates[$pointParam][1] + 20;
                        if ( !$width )
                        {
                            if ( isset( $coordinates[3][4] ) )
                            $width = $coordinates[3][4] - $coordinates[0][1];
                        }
                    }
                    else
                    {
                        break;
                    }        
                    
                    if ( $itemContent['type'] == 'image_container' )
                        $text->setAttribute( 'id', 'image_' . $content->attribute( 'id' ) );
                    else
                        $text->setAttribute( 'id', $content->attribute( 'id' ) );
                    $text->setAttribute( 'width', $width );
                    $text->setAttribute( 'x', $x );
                    $text->setAttribute( 'y', $y );
                    $text->setAttribute( 'style', 'font-family:Arial;font-size:25px;' );
                    
                    $textValue = $this->dom->createTextNode( $content->getInfoString() );
                    $text->appendChild( $textValue );
    
                    $appendTo->appendChild( $text );
                }
                break;
            
            default:
            break;
        }
    }
    
    public function output()
    {
        $return =  '<?xml version="1.0" encoding="iso-8859-1"?>';
        $return = '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.0//EN" "http://www.w3.org/TR/SVG/DTD/svg10.dtd">';
        $return = $this->dom->saveXML();
        return $return;
    }
    
    public function getSpreadDimensions()
    {
        return array(
            'width' => ceil( $this->width ) + 2,
            'height'=> ceil( $this->height ) + 2
        );
    }

}
?>
