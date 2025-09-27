( function( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { Fragment, useState } = wp.element;
	const { PanelBody, TextControl, TextareaControl, Button, Spinner, Notice } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { select } = wp.data;

	const GeminiSEOHelperSidebar = () => {
		const [ isLoading, setIsLoading ] = useState( false );
		const [ seoTitle, setSeoTitle ] = useState( '' );
		const [ metaDescription, setMetaDescription ] = useState( '' );
		const [ focusKeywords, setFocusKeywords ] = useState( '' );
		const [ error, setError ] = useState( null );

		const postContent = useSelect( ( select ) =>
			select( 'core/editor' ).getEditedPostContent()
		);

		const postId = useSelect( ( select ) =>
			select( 'core/editor' ).getCurrentPostId()
		);

		const { editPost } = useDispatch( 'core/editor' );

		const generateSeoData = async () => {
			setIsLoading( true );
			setError( null );

			try {
				const response = await wp.apiFetch( {
					path: geminiSeoHelper.restUrl,
					method: 'POST',
					data: { content: postContent },
					headers: {
						'X-WP-Nonce': geminiSeoHelper.nonce,
					},
				} );

				if ( response.success ) {
					setSeoTitle( response.data.title );
					setMetaDescription( response.data.description );
					setFocusKeywords( response.data.keywords.join( ', ' ) );

					// Optionally update post meta directly (if custom fields are used)
					// editPost({ meta: { _gemini_seo_title: response.data.title } });
					// editPost({ meta: { _gemini_meta_description: response.data.description } });
					// editPost({ meta: { _gemini_focus_keywords: response.data.keywords.join(', ') } });

				} else {
					setError( response.message );
				}
			} catch ( err ) {
				setError( err.message || geminiSeoHelper.i18n.error + ' ' + err.code );
			}

			setIsLoading( false );
		};

		return (
			<Fragment>
				<PluginSidebarMoreMenuItem
					target="gemini-seo-helper-sidebar"
					icon="superhero"
				>
					{ geminiSeoHelper.i18n.generateSeoData }
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="gemini-seo-helper-sidebar"
					title={ geminiSeoHelper.i18n.generateSeoData }
					icon="superhero"
				>
					<PanelBody title={ geminiSeoHelper.i18n.generateSeoData } initialOpen={ true }>
						{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
						<Button
							isPrimary
							onClick={ generateSeoData }
							disabled={ isLoading || ! postContent }
						>
							{ isLoading ? <Spinner /> : geminiSeoHelper.i18n.generateSeoData }
						</Button>
						{ seoTitle && (
							<TextControl
								label={ geminiSeoHelper.i18n.seoTitle }
								value={ seoTitle }
								onChange={ setSeoTitle }
							/>
						) }
						{ metaDescription && (
							<TextareaControl
								label={ geminiSeoHelper.i18n.metaDescription }
								value={ metaDescription }
								onChange={ setMetaDescription }
							/>
						) }
						{ focusKeywords && (
							<TextControl
								label={ geminiSeoHelper.i18n.focusKeywords }
								value={ focusKeywords }
								onChange={ setFocusKeywords }
							/>
						) }
					</PanelBody>
				</PluginSidebar>
			</Fragment>
		);
	};

	registerPlugin( 'gemini-seo-helper-editor', {
		render: GeminiSEOHelperSidebar,
		icon: 'superhero',
	} );
} )( window.wp );

