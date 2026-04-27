/**
** =============================================================================
** PRELOADER JAVASCRIPT - js/preloader.js
** =============================================================================
** Fades out the preloader when page finishes loading.
** =============================================================================
**/

/**
** Window Load Handler
** Hides the preloader with a fade animation.
**/
$(window).on("load", function()
{
	$("#status").fadeOut();
	$("#preloader").delay(350).fadeOut("slow");
});