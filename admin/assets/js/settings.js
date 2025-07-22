(function($) {
  'use strict';

  const editorInstances = new Map();

  const initializeEditor = ( element, type, index ) => {
    const elementID = `editor-${type}-${index}`;
    const textarea = $(element).find(`.${type}`)[0];

    if ( !textarea.id) {
      textarea.id = elementID;
    }

    const config = {
      codemirror: {
        lineNumbers: true,
        autoCloseBrackets: true,
        indentUnit: 2,
        tabSize: 2,
        foldGutter: true,
        gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
      }
    };

    if (type === 'css') {
      config.codemirror.mode = 'css';
    } else if (type === 'html') {
      config.codemirror.mode = 'htmlmixed';
      config.codemirror.autoCloseTags = true;
      config.codemirror.matchBrackets = true;
      config.codemirror.lineWrapping = true;
    }

    try {
      return wp.codeEditor.initialize(textarea.id, config);
    } catch (error) {
      console.error('Error initializing code editor:', error);
      return null;
    }
  }

  const switchEdior = ( element, type, index ) => {
    const instanceKey = `editor-${index}`;
    let instances = editorInstances.get(instanceKey) || {};
    $(element).find(`textarea.${type}`).show();

    const oppositeType = type === 'css' ? 'html' : 'css';
    if (instances[oppositeType]) {
      cleanupEditor(instances[oppositeType]);
      instances[oppositeType] = null;

      $(element).find(`textarea.${oppositeType}`).hide();
    }

    // Initialize or reuse the current editor
    if (!instances[type]) {
      instances[type] = initializeEditor(element, type, index);
    }

    // Store the updated instances
    editorInstances.set(instanceKey, instances);
  }

  const cleanupEditor = (editor) => {
    if (editor && editor.codemirror) {
      try {
        editor.codemirror.toTextArea();
      } catch (error) {
        console.error('Error cleaning up editor:', error);
      }
    }
  }

  document.querySelectorAll('.richtext-editor').forEach(function(element, index) {
    const instanceKey = `editor-${index}`;

    $(element).find('li').on( 'click', function() {
      let instances = editorInstances.get(instanceKey) || {};
      
      const type = $(this).data('type');

      $(this).addClass('active').siblings().removeClass('active');

      if ( null === instances[type] ) {
        switchEdior( element, type, index );
      }

    });

    
    const htmlTextarea = $(element).find('.html')[0];
    const cssTextarea = $(element).find('.css')[0];

    const defaultEditor = $(element).data('default-editor');

    if (htmlTextarea || cssTextarea) {
      $(element).find('textarea').hide();
      $(element).find(`textarea.${defaultEditor}`).show();

      // Initialize HTML editor by default
      const editor = initializeEditor(element, defaultEditor, index);

      editorInstances.set(instanceKey, {
        html: ( 'html' === defaultEditor ) ? editor : null,
        css: ( 'css' === defaultEditor ) ? editor : null,
      });

      // Sync codemirror with textarea.
      editor.codemirror.on('change', function() {
        editor.codemirror.save();
      });
    }

  });


})(jQuery);