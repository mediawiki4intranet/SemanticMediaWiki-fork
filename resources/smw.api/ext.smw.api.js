/**
 * SMW Api base class
 *
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 *
 * @licence GNU GPL v2 or later
 * @author mwjames
 */
( function( $, mw, smw ) {
	'use strict';

	/**
	 * Constructor to create an object to interact with the API and SMW
	 *
	 * @since 1.9
	 *
	 * @class
	 * @constructor
	 */
	smw.Api = function() {};

	/* Public methods */

	smw.Api.prototype = {

		/**
		 * Convenience method to parse and map a JSON string
		 *
		 * Emulates partly $.parseJSON (jquery.js)
		 * @see http://www.json.org/js.html
		 *
		 * @since  1.9
		 *
		 * @param {string} data
		 *
		 * @return {object|null}
		 */
		parse: function( data ) {

			// Use smw.Api JSON custom parser to resolve raw data and add
			// type hinting
			var dataItem = new smw.dataItem();

			if ( !data || typeof data !== 'string' ) {
				return null;
			}

			// Remove leading/trailing whitespace
			data = $.trim(data);

			// Attempt to parse using the native JSON parser first
			if ( window.JSON && window.JSON.parse ) {
				return JSON.parse( data, function ( key, value ) { return dataItem.factory( key, value ); } );
			}

			// If the above fails, use jquery to do the rest
			return $.parseJSON( data );
		},

		/**
		 * Returns results from the SMWAPI
		 *
		 * On the topic of converters
		 * @see http://bugs.jquery.com/ticket/9095
		 *
		 * var smwApi = new smw.Api();
		 * smwApi.fetch( query )
		 *   .done( function ( data ) { } )
		 *   .fail( function ( error ) { } );
		 *
		 *
		 * @since 1.9
		 *
		 * @param {string} queryString
		 * @param {boolean|number} useCache
		 *
		 * @return {jQuery.Promise}
		 */
		fetch: function( queryString, useCache ){
			var self = this,
				apiDeferred = $.Deferred();

			if ( !queryString || typeof queryString !== 'string' ) {
				throw new Error( 'Invalid query string: ' + queryString );
			}

			// Look for a cache object otherwise do an Ajax call
			if ( useCache ) {

				// Use a hash key to compare queries and use it as identifier for
				// stored resultObjects, each change in the queryString will result
				// in another hash key which will ensure only objects are stored
				// with this key can be reused
				var hash = md5( queryString );

				var resultObject = $.jStorage.get( hash );
				if ( resultObject !== null ) {
					var results = self.parse( resultObject );
					results.isCached = true;
					apiDeferred.resolve( results );
					return apiDeferred.promise();
				}
			}

			return $.ajax( {
				url: mw.util.wikiScript( 'api' ),
				dataType: 'json',
				data: {
					'action': 'ask',
					'format': 'json',
					'query' : queryString
					},
				converters: { 'text json': function ( data ) {
					// Store only the string as we want to return a typed object
					// If useCache is not a number use 15 min as default ttl
					if ( useCache ){
						$.jStorage.set( hash, data, {
							TTL: $.type( useCache ) === 'number' ? useCache : 900000
						} );
					}
					var results = self.parse( data );
					results.isCached = false;
					return results;
				} }
			} );
		}
	};

	//Alias
	smw.api = smw.Api;

} )( jQuery, mediaWiki, semanticMediaWiki );