jQuery(document).ready(function($){
$('button').on('click', function(){
    $('button').removeClass('event-selected');
    $(this).addClass('event-selected');
});
//$(document).ready(function(){

//    $("#default-event-selection")[0].click();

//});
$(window).load(function(){
$("#cov21Event1").click(function() {
    $('html, body').animate({
        scrollTop: $("#tab-content-section").offset().top
    }, 10);
});
  });
  
$(window).load(function(){
$("#cov21Event2").click(function() {
    $('html, body').animate({
        scrollTop: $("#tab-content-section").offset().top
    }, 10);
});
  });
  $(window).load(function(){
$("#cov21Event3").click(function() {
    $('html, body').animate({
        scrollTop: $("#tab-content-section").offset().top
    }, 10);
});
  });
  
  });
