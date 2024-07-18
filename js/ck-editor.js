CKEDITOR.editorConfig = function( config ) {
	CKEDITOR.config.htmlEncodeOutput = false;
    CKEDITOR.config.entities = false;
    CKEDITOR.dtd.$removeEmpty.span = 0;
    CKEDITOR.dtd.$removeEmpty.i = 0;
    CKEDITOR.dtd.$removeEmpty.label = 0;
};