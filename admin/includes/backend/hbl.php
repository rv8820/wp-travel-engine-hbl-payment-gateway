<?php
global $post;
$wp_travel_engine_postmeta_settings = get_post_meta( $post->ID, 'wp_travel_engine_booking_setting', true );
?>
<div class="trip-info-meta">
	<div>
	<label for="wp_travel_engine_booking_setting[place_order][payment][status]"><?php _e('Status: ','wte-hbl');?></label>	
	<input type="text" id="wp_travel_engine_booking_setting[place_order][payment][status]" name="wp_travel_engine_booking_setting[place_order][payment][status]" value="<?php echo isset($wp_travel_engine_postmeta_settings['place_order']['payment']['status']) ? esc_attr($wp_travel_engine_postmeta_settings['place_order']['payment']['status']):''?>">
	</div>
	<div>
	<label for="wp_travel_engine_booking_setting[place_order][payment][invoice]"><?php _e('Invoice: ','wte-hbl');?></label>	
	<input type="text" id="wp_travel_engine_booking_setting[place_order][payment][invoice]" name="wp_travel_engine_booking_setting[place_order][payment][invoice]" value="<?php echo isset($wp_travel_engine_postmeta_settings['place_order']['payment']['invoice']) ? esc_attr($wp_travel_engine_postmeta_settings['place_order']['payment']['invoice']):'';?>">
	</div>
	<div>
	<label for="wp_travel_engine_booking_setting[place_order][payment][rescode]"><?php _e('Response code: ','wte-hbl');?></label>	
	<input type="text" id="wp_travel_engine_booking_setting[place_order][payment][rescode]" name="wp_travel_engine_booking_setting[place_order][payment][rescode]" value="<?php echo isset($wp_travel_engine_postmeta_settings['place_order']['payment']['rescode']) ? esc_attr($wp_travel_engine_postmeta_settings['place_order']['payment']['rescode']):'';?>">
	</div>
	<div>
	<label for="wp_travel_engine_booking_setting[place_order][payment][tranRef]"><?php _e('Transaction Ref: ','wte-hbl');?></label>	
	<input type="text" id="wp_travel_engine_booking_setting[place_order][payment][tranRef]" name="wp_travel_engine_booking_setting[place_order][payment][tranRef]" value="<?php echo isset($wp_travel_engine_postmeta_settings['place_order']['payment']['tranRef']) ? esc_attr($wp_travel_engine_postmeta_settings['place_order']['payment']['tranRef']):'';?>">
	</div>
</div>