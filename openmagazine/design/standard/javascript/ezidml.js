var loadDone = function(svg, error) { 
    if (error) svg.text(10, 20, error); 
};
var hide_text = function(){
    if ($(this).val() == repositorySearch)
    $(this).val('');
};
var show_text = function(){
    if ($(this).val() == '')
    $(this).val(repositorySearch);
};
var repositoryCallback = function(carousel, state){
    var postData = {first: carousel.first,last: carousel.last, attributeID: attributeID}
    if ( $('#repositorySearch').val() != repositorySearch )
        postData.search =  $('#repositorySearch').val();
    if (carousel.has(carousel.first, carousel.last)) {
        return;
    }
    $.ez( 'ezidmlfunctionsjs::fetchRepository',
         postData,
         function(data) {
            repositoryAddCallback(carousel, carousel.first, carousel.last, data.content);
        }
    );
};
var repositoryAddCallback = function(carousel, first, last, string){
    var html = $('<div id="temp">').html( string );
    var total = parseInt($('#total', html).text());
    carousel.size( total );
    $(".repository-svg-container", html).scrollable({mousewheel: true});
    $('.repository-container', html).each(function(i) {
        carousel.add(first + i, $(this));
        $('.repository-svg').each( function(){
            var id = $(this).attr('id');
            var params = id.split('-');
            $('#'+id).svg();
            var svgWidth = $('.repository-svg').css('width').replace("px", "");
            var svgHeight = $('.repository-svg').css('height').replace("px", "");
            var svg = $('#'+id).svg('get')
            .configure({
                width: svgWidth,
                height: svgHeight
            })
            .load( '/openmagazine/svg/' + params[1] + '/'+ params[2] + '/0.2', {addTo: false, changeSize: false, onLoad: loadDone});
        });
    });
    var containerWidth = $('#select').css( 'width' );
    $('.repository-container, .jcarousel-clip, .jcarousel-list li' ).css( 'width', containerWidth );
    var listSize = $('.jcarousel-list').children('li').size();
    var listWidth = (listSize * parseInt(containerWidth) );            
    $('.jcarousel-list').css( 'width', listWidth );
};
var carouselSetup = {
    scroll:1,
    buttonNextHTML:'<div>&raquo;</div>',
    buttonPrevHTML:'<div>&laquo;</div>',
    itemLoadCallback: repositoryCallback
};
$(function() {
    $( ".tabs" ).tabs({collapsible: true});    
    $('#repository-carousel').jcarousel(carouselSetup);    
    $('.spread-container').each(
        function( e ){
            var id = $(this).attr( 'id' ).replace("spread-container-", "");
            var source = svgElements[id];
            $(this).svg({ loadURL: source });
        }
    );
    $('#repositorySearch').val(repositorySearch);
    $('#repositorySearch').bind('focus', hide_text)
    $('#repositorySearch').bind('blur', show_text)
    
    $('#search_repository').bind('click', function(){
        if ( $('#repositorySearch').val() != repositorySearch ){
            $('#repository-carousel-container').empty();
            $('<ul id="repository-carousel"></ul>')
            .addClass( "repository-carousel" )
            .appendTo( '#repository-carousel-container' )
            .jcarousel(carouselSetup);
        }
        return false;
    });
    $('#cancel_search_repository').bind('click', function(){
        if ( $('#repositorySearch').val() != repositorySearch ){
            $('#repositorySearch').val(repositorySearch);
            $('#repository-carousel-container').empty();
            $('<ul id="repository-carousel"></ul>')
            .addClass( "repository-carousel" )
            .appendTo( '#repository-carousel-container' )
            .jcarousel(carouselSetup);
        }
        return false;
    });
    
    $('.repository-helper .spinner')
    .css( 'visibility', 'hidden' )
    .ajaxStart(function() {
        $(this).css( 'visibility', 'visible' );
    })
    .ajaxStop(function() {
        $(this).css( 'visibility', 'hidden' );
    });
    //@TODO
    if (!$.browser.mozilla) $('.repository-search').hide();
});