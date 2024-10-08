/*global redux_change, redux, redux_typography_ajax, WebFont */

/**
 * Typography
 * Dependencies:        google.com, jquery, select2
 * Feature added by:    Dovy Paukstys - http://simplerain.com/
 * Date:                06.14.2013
 *
 * Rewrite:             Kevin Provance (kprovance)
 * Date:                May 25, 2014,
 * And again on:        April 4, 2017, for v4.0
 */
(function( $ ) {
  'use strict';
  
  var selVals     = [];
  var isSelecting = false;
  var proLoaded   = true;
  
  redux.field_objects            = redux.field_objects || {};
  redux.field_objects.bb_typography = redux.field_objects.bb_typography || {};
  
  redux.field_objects.bb_typography.init = function( selector ) {
   // selector = $.redux.getSelector( selector, 'bb_typography' );
    selector = $( document ).find( '.redux-group-tab:visible' ).find( '.redux-container-bb_typography:visible' );
    
    $( selector ).each(
      function() {
        var el     = $( this );
        var parent = el;
        
        if ( ! el.hasClass( 'redux-field-container' ) ) {
          parent = el.parents( '.redux-field-container:first' );
        }
        
        if ( parent.is( ':hidden' ) ) {
          return;
        }
        
        if ( undefined === redux.field_objects.pro ) {
          
          proLoaded = false;
        }
        
        el.each(
          function() {
            
            // Init each typography field.
            $( this ).find( '.redux-typography-container' ).each(
              function() {
                var el     = $( this );
                var parent = el;
                var key;
                var obj;
                var prop;
                var fontData;
                var val;
                var xx;
                var reduxTypography;
                
                var family           = $( this ).find( '.redux-typography-family' );
                var familyData       = family.data( 'value' );
                var data             = [{ id: 'none', text: 'none' }];
                var thisID           = $( this ).find( '.redux-typography-family' ).parents( '.redux-container-bb_typography:first' ).data( 'id' );
                var usingGoogleFonts = $( '#' + thisID + ' .redux-typography-google' ).val();
                
                // Set up data array.
                var buildData = [];
                var fontKids  = [];
                
                // User included fonts?
                var isUserFonts = $( '#' + thisID + ' .redux-typography-font-family' ).data( 'user-fonts' );
                
                if ( ! el.hasClass( 'redux-field-container' ) ) {
                  parent = el.parents( '.redux-field-container:first' );
                }
                
                if ( parent.is( ':hidden' ) ) {
                  return;
                }
                
                if ( parent.hasClass( 'redux-field-init' ) ) {
                  parent.removeClass( 'redux-field-init' );
                } else {
                  return;
                }
                
                if ( undefined === familyData ) {
                  family = $( this );
                } else if ( '' !== familyData ) {
                  $( family ).val( familyData );
                }
                
                isUserFonts = isUserFonts ? 1 : 0;
                
                // Google font isn't in use?
                usingGoogleFonts = usingGoogleFonts ? 1 : 0;
                
                // If custom fonts, push onto array.
                // if ( undefined !== redux.customfonts ) {
                //   buildData.push( redux.customfonts );
                // }
                if ( redux.customfonts !== undefined ) {
                  for ( var b in redux.customfonts ) {
                    buildData.push( redux.customfonts[b] );
                  }
                }
                
                // If typekit fonts, push onto array.
                if ( undefined !== redux.typekitfonts ) {
                  buildData.push( redux.typekitfonts );
                }
                
                // If standard fonts, push onto array.
                if ( undefined !== redux.stdfonts && 0 === isUserFonts ) {
                  buildData.push( redux.stdfonts );
                }
                
                // If user fonts, pull from localize and push into array.
                if ( 1 === isUserFonts ) {
                  
                  // <option>
                  for ( key in redux.optName.typography[thisID] ) {
                    if ( redux.optName.typography[thisID].hasOwnProperty( key ) ) {
                      obj = redux.optName.typography[thisID].std_font;
                      
                      for ( prop in obj ) {
                        if ( obj.hasOwnProperty( prop ) ) {
                          fontKids.push(
                            {
                              id: prop,
                              text: prop,
                              'data-google': 'false'
                            }
                          );
                        }
                      }
                    }
                  }
                  
                  // <optgroup>
                  fontData = {
                    text: 'Standard Fonts',
                    children: fontKids
                  };
                  
                  buildData.push( fontData );
                }
                
                // If googfonts on and had data, push into array.
                if ( 1 === usingGoogleFonts || true === usingGoogleFonts && undefined !== redux.googlefonts ) {
                  buildData.push( redux.googlefonts );
                }
                
                // Output data to dropdown.
                data = buildData;
                
                val = $( this ).find( '.redux-typography-family' ).data( 'value' );
                
                $( this ).find( '.redux-typography-family' ).addClass( 'ignore-change' );
                
                $( this ).find( '.redux-typography-family' ).select2( { data: data } );
                $( this ).find( '.redux-typography-family' ).val( val ).trigger( 'change' );
                
                $( this ).find( '.redux-typography-family' ).removeClass( 'ignore-change' );
                
                xx = el.find( '.redux-typography-family' );
                if ( ! xx.hasClass( 'redux-typography-family' ) ) {
                  el.find( '.redux-typography-style' ).select2();
                }
                
                $( this ).find( '.redux-typography-align' ).select2();
                $( this ).find( '.redux-typography-family-backup' ).select2();
                $( this ).find( '.redux-typography-transform' ).select2();
                $( this ).find( '.redux-typography-font-variant' ).select2();
                $( this ).find( '.redux-typography-decoration' ).select2();
                
                $( this ).find( '.redux-insights-data-we-collect-typography' ).on( 'click', function( e ) {
                  e.preventDefault();
                  $( this ).parent().find( '.description' ).toggle();
                });
                
                // Init select2 for indicated fields.
                redux.field_objects.bb_typography.select( family, true, false, null, true );
                
                // Init when value is changed.
                $( this ).find( '.redux-typography-family, .redux-typography-family-backup, .redux-typography-style, .redux-typography-subsets, .redux-typography-align' ).on(
                  'change',
                  function( val ) {
                    var getVals;
                    var fontName;
                    
                    var thisID = $( this ).attr( 'id' ), that = $( '#' + thisID );
                    
                    if ( $( this ).hasClass( 'redux-typography-family' ) ) {
                      if ( that.val() ) {
                        getVals = $( this ).select2( 'data' );
                        if ( getVals ) {
                          fontName = getVals[0].text;
                        } else {
                          fontName = null;
                        }
                        
                        that.data( 'value', fontName );
                        
                        selVals = getVals[0];
                        
                        isSelecting = true;
                        
                        redux.field_objects.bb_typography.select( that, true, false, fontName, true );
                      }
                    } else {
                      val = that.val();
                      
                      that.data( 'value', val );
                      
                      if ( $( this ).hasClass( 'redux-typography-align' ) ||
                           $( this ).hasClass( 'redux-typography-subsets' ) ||
                           $( this ).hasClass( 'redux-typography-family-backup' ) ||
                           $( this ).hasClass( 'redux-typography-transform' ) ||
                           $( this ).hasClass( 'redux-typography-font-variant' ) ||
                           $( this ).hasClass( 'redux-typography-decoration' ) ) {
                        that.find( 'option[selected="selected"]' ).attr( 'selected', false );
                        that.find( 'option[value="' + val + '"]' ).attr( 'selected', 'selected' );
                      }
                      
                      if ( $( this ).hasClass( 'redux-typography-subsets' ) ) {
                        that.siblings( '.typography-subsets' ).val( val );
                      }
                      
                      redux.field_objects.bb_typography.select( $( this ), true, false, null, false );
                    }
                  }
                );
                
                // Init when value is changed.
                $( this ).find( '.redux-typography-size, .redux-typography-height, .redux-typography-word, .redux-typography-letter, .redux-typography-margin-top, .redux-typography-margin-bottom' ).on(
                  'keyup',
                  function() {
                    redux.field_objects.bb_typography.select( $( this ).parents( '.redux-container-bb_typography:first' ) );
                  }
                );

                // Init when value is changed.
                $( this ).find( '.redux-typography-family' ).on(
                  'change',
                  function() {
                    redux.field_objects.bb_typography.select( $( this ).parents( '.redux-container-bb_typography:first' ) );
                  }
                );
                
                // Have to redeclare the wpColorPicker to get a callback function.
                $( this ).find( '.redux-typography-color, .redux-typography-shadow-color' ).wpColorPicker(
                  {
                    change: function( e, ui ) {
                      e = null;
                      $( this ).val( ui.color.toString() );
                      redux.field_objects.bb_typography.select( $( this ).parents( '.redux-container-bb_typography:first' ) );
                    }
                  }
                );
                
                // Don't allow negative numbers for size field.
                $( this ).find( '.redux-typography-size' ).numeric( { allowMinus: false } );
                
                // Allow negative numbers for indicated fields.
                $( this ).find( '.redux-typography-height, .redux-typography-word, .redux-typography-letter' ).numeric( { allowMinus: true } );
                
                reduxTypography = $( this ).find( '.redux-typography' );
                
                reduxTypography.on(
                  'select2:unselecting',
                  function() {
                    var thisID;
                    var that;
                    
                    var opts = $( this ).data( 'select2' ).options;
                    
                    opts.set( 'disabled', true );
                    setTimeout(
                      function() {
                        opts.set( 'disabled', false );
                      },
                      1
                    );
                    
                    thisID = $( this ).attr( 'id' );
                    that   = $( '#' + thisID );
                    
                    that.data( 'value', '' );
                    
                    if ( $( this ).hasClass( 'redux-typography-family' ) ) {
                      $( this ).find( '.redux-typography-family' ).addClass( 'ignore-change' );
                      $( this ).val( null ).trigger( 'change' );
                      $( this ).find( '.redux-typography-family' ).removeClass( 'ignore-change' );
                      
                      redux.field_objects.bb_typography.select( that, true, false, null, true );
                    } else {
                      if ( $( this ).hasClass( 'redux-typography-align' ) ||
                           $( this ).hasClass( 'redux-typography-subsets' ) ||
                           $( this ).hasClass( 'redux-typography-family-backup' ) ||
                           $( this ).hasClass( 'redux-typography-transform' ) ||
                           $( this ).hasClass( 'redux-typography-font-variant' ) ||
                           $( this ).hasClass( 'redux-typography-decoration' ) ) {
                        $( '#' + thisID + ' option[selected="selected"]' ).removeAttr( 'selected' );
                      }
                      
                      if ( $( this ).hasClass( 'redux-typography-subsets' ) ) {
                        that.siblings( '.typography-subsets' ).val( '' );
                      }
                      
                      if ( $( this ).hasClass( 'redux-typography-family-backup' ) ) {
                        $( this ).find( '.redux-typography-family-backup' ).addClass( 'ignore-change' );
                        that.val( null ).trigger( 'change' );
                        $( this ).find( '.redux-typography-family-backup' ).removeClass( 'ignore-change' );
                      }
                      
                      redux.field_objects.bb_typography.select( $( this ), true, false, null, false );
                    }
                  }
                );
                
                window.onbeforeunload = null;
                parent.removeClass( 'redux-field-init' );
                
                if ( ! proLoaded ) {
                  redux.field_objects.bb_typography.sliderInit( el );
                }
              }
            );
          }
        );
      }
    );
  };
  
  redux.field_objects.bb_typography.sliderInit = function( el ) {
    el.find( '.redux-typography-slider' ).each(
      function() {
        var mainID = $( this ).data( 'id' );
        var minVal = $( this ).data( 'min' );
        var maxVal = $( this ).data( 'max' );
        var step   = $( this ).data( 'step' );
        var def    = $( this ).data( 'default' );
        var label  = $( this ).data( 'label' );
        var rtl    = Boolean( $( this ).data( 'rtl' ) );
        var range  = [minVal, maxVal];
        
        var slider = $( this ).reduxNoUiSlider(
          {
            range: range,
            start: def,
            handles: 1,
            step: step,
            connect: 'lower',
            behaviour: 'tap-drag',
            rtl: rtl,
            serialization: {
              resolution: 1
            },
            slide: function() {
              $( this ).next( '#redux-slider-value-' + mainID ).attr( 'value', slider.val() );
              
              $( this ).prev( 'label' ).html(
                label + ':  <strong>' + slider.val() + 'px</strong>'
              );
              
              redux.field_objects.bb_typography.select( el );
            }
          }
        );
      }
    );
  };
  // Return font size.
  redux.field_objects.bb_typography.size = function( obj ) {
    var size = 0;
    var key;
    
    for ( key in obj ) {
      if ( obj.hasOwnProperty( key ) ) {
        size += 1;
      }
    }
    
    return size;
  };
  
  // Return proper bool value.
  redux.field_objects.bb_typography.makeBool = function( val ) {
    if ( 'false' === val || '0' === val || false === val || 0 === val ) {
      return false;
    } else if ( 'true' === val || '1' === val || true === val || 1 === val ) {
      return true;
    }
  };
  
  redux.field_objects.bb_typography.contrastColour = function( hexcolour ) {
    var r;
    var b;
    var g;
    var res;
    
    // Default value is black.
    var retVal = '#444444';
    
    // In case - for some reason - a blank value is passed.
    // This should *not* happen.  If a function passing a value
    // is canceled, it should pass the current value instead of
    // a blank.  This is how the Windows Common Controls do it.  :P .
    if ( '' !== hexcolour ) {
      
      // Replace the hash with a blank.
      hexcolour = hexcolour.replace( '#', '' );
      
      r   = parseInt( hexcolour.substr( 0, 2 ), 16 );
      g   = parseInt( hexcolour.substr( 2, 2 ), 16 );
      b   = parseInt( hexcolour.substr( 4, 2 ), 16 );
      res = ( ( r * 299 ) + ( g * 587 ) + ( b * 114 ) ) / 1000;
      
      // Instead of pure black, I opted to use WP 3.8 black, so it looks uniform.  :) - kp.
      retVal = ( res >= 128 ) ? '#444444' : '#ffffff';
    }
    
    return retVal;
  };
  
  // Sync up font options.
  redux.field_objects.bb_typography.select = function( selector, skipCheck, destroy, fontName, active ) {
    var mainID;
    var that;
    var family;
    var google;
    var familyBackup;
    var size;
    var height;
    var word;
    var letter;
    var align;
    var transform;
    var fontVariant;
    var decoration;
    var style;
    var script;
    var color;
    var units;
    var _linkclass;
    var the_font;
    var link;
    var isPreviewSize;
    var marginTop;
    var marginBottom;
    
    var typekit              = false;
    //var details              = '';
    var html                 = '<option value=""></option>';
    var selected             = '';
    var allowEmptyLineHeight = false;
    var details = {
      '400': 'Normal 400',
      '700': 'Bold 700',
      '400italic': 'Normal 400 Italic',
      '700italic': 'Bold 700 Italic'
    };
    
    // Main id for selected field.
    mainID = $( selector ).parents( '.redux-container-bb_typography:first' ).data( 'id' );
    if ( undefined === mainID ) {
      mainID = $( selector ).data( 'id' );
    }
    
    that   = $( '#' + mainID );
    family = $( '#' + mainID + '-family' ).val();
    
    if ( ! family ) {
      family = null; // 'inherit';
    }
    
    if ( fontName ) {
      family = fontName;
    }
    
    familyBackup = that.find( 'select.redux-typography-family-backup' ).val();
    size         = that.find( '.redux-typography-size' ).val();
    height       = that.find( '.redux-typography-height' ).val();
    word         = that.find( '.redux-typography-word' ).val();
    letter       = that.find( '.redux-typography-letter' ).val();
    align        = that.find( 'select.redux-typography-align' ).val();
    transform    = that.find( 'select.redux-typography-transform' ).val();
    fontVariant  = that.find( 'select.redux-typography-font-variant' ).val();
    decoration   = that.find( 'select.redux-typography-decoration' ).val();
    style        = that.find( 'select.redux-typography-style' ).val();
    script       = that.find( 'select.redux-typography-subsets' ).val();
    color        = that.find( '.redux-typography-color' ).val();
    marginTop    = that.find( '.redux-typography-margin-top' ).val();
    marginBottom = that.find( '.redux-typography-margin-bottom' ).val();
    units        = that.data( 'units' );
    
    // Is selected font a google font?
    if ( true === isSelecting ) {
      google = redux.field_objects.bb_typography.makeBool( selVals['data-google'] );
      that.find( '.redux-typography-google-font' ).val( google );
    } else {
      google = redux.field_objects.bb_typography.makeBool( that.find( '.redux-typography-google-font' ).val() ); // Check if font is a google font.
    }
    if ( active ) {
      
      // Page load. Speeds things up memory wise to offload to client.
      if ( ! that.hasClass( 'typography-initialized' ) ) {
        style  = that.find( 'select.redux-typography-style' ).data( 'value' );
        script = that.find( 'select.redux-typography-subsets' ).data( 'value' );
        
        if ( '' !== style ) {
          style = String( style );
        }
        
        if ( undefined !== typeof ( script ) ) {
          script = String( script );
        }
      }
      
      // Something went wrong trying to read google fonts, so turn google off.
      if ( undefined === redux.fonts.google ) {
        google = false;
      }
  
      // Get font details
      var details = {
        '400': 'Normal 400',
        '700': 'Bold 700',
        '400italic': 'Normal 400 Italic',
        '700italic': 'Bold 700 Italic'
      };
      
      // Get font details.
      if ( true === google && ( family in redux.fonts.google ) ) {
        details = redux.fonts.google[family];
      } else if ( redux.customfonts !== undefined ) {
        for ( var b in redux.customfonts ) {
          if ( redux.customfonts[b]['children'] !== undefined && redux.customfonts[b]['children'].length ) {
            for ( var c in redux.customfonts[b]['children'] ) {
              if ( redux.customfonts[b]['children'][c]['id'] == family ) {
                details = redux.customfonts[b]['children'][c]['variants'];
                break;
              }
            }
          }
        }
      }
      
      if ( $( selector ).hasClass( 'redux-typography-subsets' ) ) {
        that.find( 'input.typography-subsets' ).val( script );
      }
      
      // If we changed the font.
      if ( $( selector ).hasClass( 'redux-typography-family' ) ) {
        
        // Google specific stuff.
        if ( true === google ) {
          
          // STYLES.
          $.each(
            details.variants,
            function( index, variant ) {
              index = null;
              if ( variant.id === style || 1 === redux.field_objects.bb_typography.size( details.variants ) ) {
                selected = ' selected="selected"';
                style    = variant.id;
              } else {
                selected = '';
              }
              
              html += '<option value="' + variant.id + '"' + selected + '>' + variant.name.replace( /\+/g, ' ' ) + '</option>';
            }
          );
          
          // Destroy select2.
          if ( destroy ) {
            that.find( '.redux-typography-style' ).select2( 'destroy' );
          }
          // Instert new HTML.
          that.find( '.redux-typography-style' ).html( html ).select2();
          
          // SUBSETS.
          selected = '';
          html     = '<option value=""></option>';
          
          $.each(
            details.subsets,
            function( index, subset ) {
              index = null;
              if ( script === subset.id || 1 === redux.field_objects.bb_typography.size( details.subsets ) ) {
                selected = ' selected="selected"';
                script   = subset.id;
                that.find( 'input.typography-subsets' ).val( script );
              } else {
                selected = '';
              }
              html += '<option value="' + subset.id + '"' + selected + '>' + subset.name.replace( /\+/g, ' ' ) + '</option>';
            }
          );
          
          // Destroy select2.
          if ( destroy ) {
            that.find( '.redux-typography-subsets' ).select2( 'destroy' );
          }
          
          // Inset new HTML.
          that.find( '.redux-typography-subsets' ).html( html ).select2( { width:'100%' } );
          
          that.find( '.redux-typography-subsets' ).parent().fadeIn( 'fast' );
          that.find( '.typography-family-backup' ).fadeIn( 'fast' );
        } else {
          if ( that.find( '.redux-typography-style' ) ) {
            $.each(
              details,
              function( index, value ) {
                if ( style === index || 'normal' === index ) {
                  selected = ' selected="selected"';
                  that.find( '.typography-style select2-selection__rendered' ).text( value );
                } else {
                  selected = '';
                }
                
                html += '<option value="' + index + '"' + selected + '>' + value.replace( '+', ' ' ) + '</option>';
              }
            );
            
            // Destory select2.
            if ( destroy ) {
              that.find( '.redux-typography-style' ).select2( 'destroy' );
            }
            // Insert new HTML.
            that.find( '.redux-typography-style' ).html( html ).select2();
  
            // Prettify things
            that.find( '.redux-typography-subsets' ).parent().fadeOut( 'fast' );
            that.find( '.typography-family-backup' ).fadeOut( 'fast' );
          }
        }
        
        that.find( '.redux-typography-font-family' ).val( family );
      } else if ( $( selector ).hasClass( 'redux-typography-family-backup' ) && '' !== familyBackup ) {
        that.find( '.redux-typography-font-family-backup' ).val( familyBackup );
      } else {
        details = default_font_weights;
        if ( details ) {
          $.each(
            details,
            function( index, value ) {
              if ( style === index || 'normal' === index ) {
                selected = ' selected="selected"';
                that.find( '.typography-style select2-selection__rendered' ).text( value );
              } else {
                selected = '';
              }
              
              html += '<option value="' + index + '"' + selected + '>' + value.replace( '+', ' ' ) + '</option>';
            }
          );
          
          // Destory select2.
          if ( destroy ) {
            that.find( '.redux-typography-style' ).select2( 'destroy' );
          }
          
          // Insert new HTML.
          that.find( '.redux-typography-style' ).html( html ).select2();
          
          // Prettify things.
          that.find( '.redux-typography-subsets' ).parent().fadeOut( 'fast' );
          that.find( '.typography-family-backup' ).fadeOut( 'fast' );
        }
      }
    }
    
    if ( active ) {
      
      that.find( '.redux-typography-style' ).addClass( 'ignore-change' );
      
      // Check if the selected value exists. If not, empty it. Else, apply it.
      if ( 0 === that.find( 'select.redux-typography-style option[value=\'' + style + '\']' ).length ) {
        style = '';
        that.find( 'select.redux-typography-style' ).val( '' ).trigger( 'change' );
      } else if ( '400' === style ) {
        that.find( 'select.redux-typography-style' ).val( style ).trigger( 'change' );
      }
      
      that.find( '.redux-typography-style' ).removeClass( 'ignore-change' );
      
      // Handle empty subset select.
      if ( 0 === that.find( 'select.redux-typography-subsets option[value=\'' + script + '\']' ).length ) {
        script = '';
        
        that.find( '.redux-typography-style' ).addClass( 'ignore-change' );
        that.find( 'select.redux-typography-subsets' ).val( '' ).trigger( 'change' );
        that.find( 'input.typography-subsets' ).val( script );
        that.find( '.redux-typography-style' ).removeClass( 'ignore-change' );
      }
    }
    
    _linkclass = 'style_link_' + mainID;
    
    // Remove other elements crested in <head>.
    $( '.' + _linkclass ).remove();
    
    if ( null !== family && 'inherit' !== family && that.hasClass( 'typography-initialized' ) ) {
      
      // Replace spaces with "+" sign.
      the_font = family.replace( /\s+/g, '+' );
      
      if ( true === google ) {
        
        // Add reference to google font family.
        link = the_font;
        
        if ( style && '' !== style ) {
          link += ':' + style.replace( /\-/g, ' ' );
        }
        
        if ( script && '' !== script ) {
          link += '&subset=' + script;
        }
        
        if ( false === isSelecting ) {
          if ( 'undefined' !== typeof ( WebFont ) && WebFont ) {
            WebFont.load( { google: { families: [link] } } );
          }
        }
        
        that.find( '.redux-typography-google' ).val( true );
      } else {
        that.find( '.redux-typography-google' ).val( false );
      }
    }
    
    // Weight and italic.
    if ( style && - 1 !== style.indexOf( 'italic' ) ) {
      that.find( '.typography-preview' ).css( 'font-style', 'italic' );
      that.find( '.typography-font-style' ).val( 'italic' );
      style = style.replace( 'italic', '' );
    } else {
      that.find( '.typography-preview' ).css( 'font-style', 'normal' );
      that.find( '.typography-font-style' ).val( '' );
    }
    
    that.find( '.typography-font-weight' ).val( style );
    
    allowEmptyLineHeight = Boolean( that.find( '.redux-typography-height' ).data( 'allow-empty' ) );
    
    if ( ! allowEmptyLineHeight ) {
      if ( ! height ) {
        height = size;
      }
    }
    
    if ( '' === size || undefined === size ) {
      that.find( '.typography-font-size' ).val( '' );
    } else {
      that.find( '.typography-font-size' ).val( size + units );
    }
    
    if ( '' === height || undefined === height ) {
      that.find( '.typography-line-height' ).val( '' );
    } else {
      that.find( '.typography-line-height' ).val( height + units );
    }
    
    if ( '' === word || undefined === word ) {
      that.find( '.typography-word-spacing' ).val( '' );
    } else {
      that.find( '.typography-word-spacing' ).val( word + units );
    }
    
    if ( '' === letter || undefined === letter ) {
      that.find( '.typography-letter-spacing' ).val( '' );
    } else {
      that.find( '.typography-letter-spacing' ).val( letter + units );
    }
    
    if ( '' === marginTop || undefined === marginTop ) {
      that.find( '.typography-margin-top' ).val( '' );
    } else {
      that.find( '.typography-margin-top' ).val( marginTop + units );
    }
    
    if ( '' === marginBottom || undefined === marginBottom ) {
      that.find( '.typography-margin-bottom' ).val( '' );
    } else {
      that.find( '.typography-margin-bottom' ).val( marginBottom + units );
    }
    
    // Show more preview stuff.
    if ( that.hasClass( 'typography-initialized' ) ) {
      isPreviewSize = that.find( '.typography-preview' ).data( 'preview-size' );
      
      if ( 0 === isPreviewSize ) {
        that.find( '.typography-preview' ).css( 'font-size', size + units );
      }

      // Make sure to wrap Baloo fonts in quotes
      if( family.indexOf('Baloo') !== -1 ) {
        family =  '"' + family + '"';
      }

      that.find( '.typography-preview' ).css(
        {
          'font-weight': style,
          'text-align': align,
          'font-family': family + ', sans-serif',
          'padding-top': marginTop + units,
          'padding-bottom': marginBottom + units
        }
      );
      
      if ( 'none' === family && '' === family ) {
        
        // If selected is not a font remove style 'font-family' at preview box.
        that.find( '.typography-preview' ).css( 'font-family', 'inherit' );
      }
      
      that.find( '.typography-preview' ).css(
        {
          'line-height': height + units,
          'word-spacing': word + units,
          'letter-spacing': letter + units
        }
      );
      
      if ( color ) {
        that.find( '.typography-preview' ).css( 'color', color );
      }
      
      if ( ! proLoaded ) {
        redux.field_objects.bb_typography.previewShadow( mainID );
      }
      
      that.find( '.typography-style select2-selection__rendered' ).text( that.find( '.redux-typography-style option:selected' ).text() );
      
      that.find( '.typography-script select2-selection__rendered' ).text( that.find( '.redux-typography-subsets option:selected' ).text() );
      
      if ( align ) {
        that.find( '.typography-preview' ).css( 'text-align', align );
      }
      
      if ( transform ) {
        that.find( '.typography-preview' ).css( 'text-transform', transform );
      }
      
      if ( fontVariant ) {
        that.find( '.typography-preview' ).css( 'font-variant', fontVariant );
      }
      
      if ( decoration ) {
        that.find( '.typography-preview' ).css( 'text-decoration', decoration );
      }
      that.find( '.typography-preview' ).slideDown();
    }
    
    // End preview stuff.
    // If not preview showing, then set preview to show.
    if ( ! that.hasClass( 'typography-initialized' ) ) {
      that.addClass( 'typography-initialized' );
    }
    
    
    isSelecting = false;
    
    if ( ! skipCheck ) {
      redux_change( selector );
    }
  };
  
  redux.field_objects.bb_typography.previewShadow = function( mainID ) {
    var shadowColor = $( '#' + mainID + ' .redux-typography-shadow-color' ).val();
    var shadowHorz  = $( '#redux-slider-value-' + mainID + '-h' ).val();
    var shadowVert  = $( '#redux-slider-value-' + mainID + '-v' ).val();
    var shadowBlur  = $( '#redux-slider-value-' + mainID + '-b' ).val();
    
    if ( shadowColor ) {
      $( '#' + mainID + ' .typography-preview' ).css(
        'text-shadow',
        shadowHorz + 'px ' + shadowVert + 'px ' + shadowBlur + 'px ' + shadowColor
      );
    }
  };
})( jQuery );
