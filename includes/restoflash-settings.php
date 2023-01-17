<?php

$form_fields = array(
	'enabled' => array(
		'title'   => __( 'Activer/Désactiver', 'woo-restoflash-gateway' ),
		'type'    => 'checkbox',
		'label'   => __( 'Activer les paiements Resto Flash pour WooCommerce', 'woo-restoflash-gateway' ),
		'default' => 'no',
	),
	'testmode' => array(
		'title'       => __( 'Mode', 'woo-restoflash-gateway' ),
		'type'        => 'select',
		'label'       => __( 'Sélectionnez le mode de serveur', 'woo-restoflash-gateway' ),
		'options'     => array(
			'TEST' => __( 'Serveur de test', 'woo-restoflash-gateway' ),
			'PROD' => __( 'Serveur de production', 'woo-restoflash-gateway' ),
		),
		'default'     => 'TEST',
		'description' => __( 'Le serveur de test permet de simuler des transactions Resto Flash', 'woo-restoflash-gateway' ),
	),	
	'api_login' => array(
		'title'       => __( 'Login Resto Flash', 'woo-restoflash-gateway' ),
		'type'        => 'text',
		'description' => __( 'Login fourni par Resto Flash', 'woo-restoflash-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'api_password' => array(
		'title'       => __( 'Mot de passe Resto Flash', 'woo-restoflash-gateway' ),
		'type'        => 'password',
		'description' => __( 'Mot de passe fourni par Resto Flash', 'woo-restoflash-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'restoflash_imei' => array(
		'title'       => __( 'IMEI', 'woo-restoflash-gateway' ),
		'type'        => 'text',
		'description' => __('Code “IMEI” construit sous la forme EDITEUR[SIRET]', 'woo-restoflash-gateway' ),
        'label'       => __( 'Veuillez entrer votre code IMEI', 'woo-restoflash-gateway' ),
		'default'     => '',
		'desc_tip'    => true,
	),
	'description' => array(
		'title'       => __( 'Description', 'woo-restoflash-gateway' ),
		'type'        => 'textarea',
		'description' => __( 'Description de la méthode de paiement pour le client', 'woo-restoflash-gateway' ),
		'default'     => __( 'Payez avec votre compte Resto Flash.', 'woo-restoflash-gateway' ),
		'desc_tip'    => true,
	),
	
);

return $form_fields;

