( function( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { Fragment, useState, useEffect } = wp.element;
	const { PanelBody, TextControl, TextareaControl, Button, Spinner, Notice } = wp.components;
	const { useSelect, useDispatch } = wp.data;

	const GeminiSEOHelperSidebar = () => {
		const [ isLoading, setIsLoading ] = useState( false );
		const [ isApplying, setIsApplying ] = useState( false );
		const [ seoTitle, setSeoTitle ] = useState( '' );
		const [ metaDescription, setMetaDescription ] = useState( '' );
		const [ focusKeywords, setFocusKeywords ] = useState( '' );
		const [ error, setError ] = useState( null );
		const [ successMessage, setSuccessMessage ] = useState( null );

		const postContent = useSelect( ( select ) =>
			select( 'core/editor' ).getEditedPostContent()
		);

		const postId = useSelect( ( select ) =>
			select( 'core/editor' ).getCurrentPostId()
		);

		const { editPost } = useDispatch( 'core/editor' );

		// Fetch existing SEO data if available (e.g., from custom fields or other SEO plugins)
		useEffect(() => {
			if (postId) {
				// This is a placeholder. In a real scenario, you'd fetch existing meta from the REST API.
				// For now, we'll assume these are empty until generated.
				// const existingTitle = select('core/editor').getEditedPostAttribute('meta')['_gemini_seo_title'];
				// if (existingTitle) setSeoTitle(existingTitle);
			}
		}, [postId]);

		const generateSeoData = async () => {
			setIsLoading( true );
			setError( null );
			setSuccessMessage( null );

			if ( ! postId ) {
				setError( geminiSeoHelper.i18n.noPostId );
				setIsLoading( false );
				return;
			}

			try {
				const response = await wp.apiFetch( {
					path: `${geminiSeoHelper.restUrl}/${postId}`,
					method: 'POST',
					// Content is now optionally sent, backend will fetch if not provided.
					data: { content: postContent }, // Still send for initial analysis if available
					headers: {
						'X-WP-Nonce': geminiSeoHelper.nonce,
					},
				} );

				if ( response.success ) {
					setSeoTitle( response.data.title );
					setMetaDescription( response.data.description );
					setFocusKeywords( response.data.keywords.join( ', ' ) );
					setSuccessMessage( geminiSeoHelper.i18n.generatedSuccessfully );
				} else {
					setError( response.message || geminiSeoHelper.i18n.error + ' ' + response.code );
				}
			} catch ( err ) {
				setError( err.message || geminiSeoHelper.i18n.error + ' ' + err.code );
			}

			setIsLoading( false );
		};

		const applySeoData = async () => {
			setIsApplying( true );
			setError( null );
			setSuccessMessage( null );

			if ( ! postId ) {
				setError( geminiSeoHelper.i18n.noPostId );
				setIsApplying( false );
				return;
			}

			try {
				const response = await wp.apiFetch( {
					path: `${geminiSeoHelper.restUrl.replace('generate-seo-data', 'apply-seo-data')}/${postId}`,
					method: 'POST',
					data: {
						seo_title: seoTitle,
						meta_description: metaDescription,
						focus_keywords: focusKeywords,
					},
					headers: {
						'X-WP-Nonce': geminiSeoHelper.nonce,
					},
				} );

				if ( response.success ) {
					setSuccessMessage( response.message );
					// Optionally, you might want to refresh the editor to show changes
					// or dispatch an action to update the post meta in the editor state.
				} else {
					setError( response.message || geminiSeoHelper.i18n.error + ' ' + response.code );
				}
			} catch ( err ) {
				setError( err.message || geminiSeoHelper.i18n.error + ' ' + err.code );
			}

			setIsApplying( false );
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
						{ successMessage && <Notice status="success" isDismissible={ false }>{ successMessage }</Notice> }
						<Button
							isPrimary
							onClick={ generateSeoData }
							disabled={ isLoading || ! postId }
						>
							{ isLoading ? <Spinner /> : geminiSeoHelper.i18n.generateSeoData }
						</Button>
						{ seoTitle !== '' && (
							<TextControl
								label={ geminiSeoHelper.i18n.seoTitle }
								value={ seoTitle }
								onChange={ setSeoTitle }
							/>
						) }
						{ metaDescription !== '' && (
							<TextareaControl
								label={ geminiSeoHelper.i18n.metaDescription }
								value={ metaDescription }
								onChange={ setMetaDescription }
							/>
						) }
						{ focusKeywords !== '' && (
							<TextControl
								label={ geminiSeoHelper.i18n.focusKeywords }
								value={ focusKeywords }
								onChange={ setFocusKeywords }
							/>
						) }
						{ (seoTitle !== '' || metaDescription !== '' || focusKeywords !== '') && (
							<Button
								isSecondary
								onClick={ applySeoData }
								disabled={ isApplying }
								style={{ marginTop: '10px' }}
							>
								{ isApplying ? <Spinner /> : geminiSeoHelper.i18n.applySeoData }
							</Button>
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

