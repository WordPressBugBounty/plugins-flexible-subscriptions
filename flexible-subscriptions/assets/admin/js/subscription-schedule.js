jQuery(
	function ($) {
		const scheduleForm = $( '#billing-schedule' );

		// Add error message spans after each input
		scheduleForm.find( 'input[type="datetime-local"]' ).each(
			function () {
				$( this ).after( '<span class="error-message" style="display:block;"></span>' );
			}
		);

		// Add warning message span after current_period_end input
		scheduleForm.find( '#current_period_end' ).after( '<span class="warning-message" style="color:orange; display:block;"></span>' );

		function showMessage(elementId, message, type = 'error') {
			scheduleForm.find( `#${elementId}` ).siblings( `.${type}-message` ).text( message ).show();
		}

		function clearMessage(elementId, type = 'any') {
			scheduleForm.find( `#${elementId}` ).siblings( type === 'any' ? '.error-message, .warning-message' : `.${type}-message` ).text( '' ).hide();
		}

		function validateDates() {
			const startDate       = scheduleForm.find( '#start_date' ).val() ? new Date( scheduleForm.find( '#start_date' ).val() ) : null;
			const trialEndDate    = scheduleForm.find( '#trial_end_date' ).val() ? new Date( scheduleForm.find( '#trial_end_date' ).val() ) : null;
			const lastPaymentDate = scheduleForm.find( '#last_payment_request_date' ).val() ? new Date( scheduleForm.find( '#last_payment_request_date' ).val() ) : null;
			const nextPaymentDate = scheduleForm.find( '#current_period_end' ).val() ? new Date( scheduleForm.find( '#current_period_end' ).val() ) : null;
			const endDate         = scheduleForm.find( '#end_date' ).val() ? new Date( scheduleForm.find( '#end_date' ).val() ) : null;
			const now             = new Date();

			// Start Date Validation
			if (startDate && scheduleForm.find( '#start_date' ).is(':not(:disabled)') && startDate < now) {
				showMessage( 'start_date', wp.i18n.__( 'Please select a future start date.', 'flexible-subscriptions' ) );
			} else {
				clearMessage( 'start_date' );
			}

			// Trial End Date Validations
			if (trialEndDate) {
				if (endDate && trialEndDate > endDate) {
					showMessage( 'trial_end_date', wp.i18n.__( 'Trial end date should be before the subscription end date.', 'flexible-subscriptions' ) );
				} else if (startDate && trialEndDate < startDate) {
					showMessage( 'trial_end_date', wp.i18n.__( 'Trial end date should be after the start date.', 'flexible-subscriptions' ) );
				} else if ( nextPaymentDate && trialEndDate > nextPaymentDate ) {
					showMessage( 'trial_end_date', wp.i18n.__( 'Trial end date cannot be after the next payment date.', 'flexible-subscriptions' ) );
				} else {
					clearMessage( 'trial_end_date' );
				}
			} else {
				clearMessage( 'trial_end_date' );
			}

			// End Date Validation
			if (endDate && startDate && endDate <= startDate) {
				showMessage( 'end_date', wp.i18n.__( 'End date must be after the start date.', 'flexible-subscriptions' ) );
			} else if (endDate && lastPaymentDate && endDate <= lastPaymentDate) {
				showMessage( 'end_date', wp.i18n.__( 'End date must be after the last payment date date.', 'flexible-subscriptions' ) );
			} else {
				clearMessage( 'end_date' );
			}

			// Next Payment Date Validations
			if (nextPaymentDate) {
				if (startDate && nextPaymentDate <= startDate) {
					showMessage( 'current_period_end', wp.i18n.__( 'Next payment date must be after the start date.', 'flexible-subscriptions' ) );
				} else if (lastPaymentDate && nextPaymentDate <= lastPaymentDate) {
					showMessage( 'current_period_end', wp.i18n.__( 'Next payment date must be after the last payment date.', 'flexible-subscriptions' ) );
				} else if (endDate && nextPaymentDate >= endDate) {
					showMessage( 'current_period_end', wp.i18n.__( 'Next payment date should be before the end date.', 'flexible-subscriptions' ) );
				} else if (nextPaymentDate < now) {
					showMessage( 'current_period_end', wp.i18n.__( 'Next payment date cannot be in the past.', 'flexible-subscriptions' ) );
				} else {
					clearMessage( 'current_period_end', 'error' );
				}
			}
		}

		// Update next payment date when billing frequency changes
		scheduleForm.find( '#billing_interval, #billing_period' ).on( 'change', updateNextPaymentDate );

		// Update next payment date when trail end date changes
		scheduleForm.find( '#trial_end_date' ).on( 'change', alignNextPaymentDateToTrialEndDate );

		// Validate on any date input change
		scheduleForm.find( 'input[type="datetime-local"], #billing_interval, #billing_period' ).on( 'change', validateDates );

		// Store initial next payment date on page load
		scheduleForm.find( '#current_period_end' ).data( 'original-date', scheduleForm.find( '#current_period_end' ).val() );

		// Update the original date if user manually changes the next payment date, mousedown fires before datepicker opens
		scheduleForm.find( '#current_period_end' ).on(
			'mousedown',
			function () {
				$( this ).data( 'original-date', $( this ).val() );
				clearMessage( 'current_period_end' );
			}
		);

		function alignNextPaymentDateToTrialEndDate() {
			const $lastPaymentDate = scheduleForm.find( '#last_payment_request_date' );

			if ( $lastPaymentDate.length > 0 ) {
				return; // Don't update if there has been any payments.
			}

			const trialEndDate = scheduleForm.find( '#trial_end_date' ).val() ? new Date( scheduleForm.find( '#trial_end_date' ).val() ) : null;
			if ( ! trialEndDate) {
				return; // Don't update if trial end date is empty.
			}

			const nextDateString = trialEndDate.toISOString().slice( 0, 16 );

			// Get the original date from the data attribute
			const originalNextPaymentDate = scheduleForm.find( '#current_period_end' ).data( 'original-date' );

			// Show warning if next payment date was changed and original value exists
			if (originalNextPaymentDate && originalNextPaymentDate !== nextDateString) {
				const message = wp.i18n.__( 'Next payment date aligned to trial end date.', 'flexible-subscriptions' );
				clearMessage( 'current_period_end' );
				showMessage( 'current_period_end', message, 'warning' );
			}
			scheduleForm.find( '#current_period_end' ).val( nextDateString );
		}

		// Update next payment date when billing frequency changes
		function updateNextPaymentDate() {
			let startDate = scheduleForm.find( '#current_period_start' ).val() ? new Date( scheduleForm.find( '#current_period_start' ).val() ) : null;

			if ( ! startDate) {
				// Maybe we have a start date set, usually on new manual subscriptions.
				startDate = scheduleForm.find( '#start_date' ).val() ? new Date( scheduleForm.find( '#start_date' ).val() ) : null;
			}

			if ( ! startDate) {
				return; // Don't update if start date is empty.
			}

			const interval = parseInt( scheduleForm.find( '#billing_interval' ).val(), 10 );
			const period   = scheduleForm.find( '#billing_period' ).val();
			let nextDate   = new Date( startDate );

			switch (period) {
				case 'D':
					nextDate.setDate( nextDate.getDate() + interval );
					break;
				case 'W':
					nextDate.setDate( nextDate.getDate() + (interval * 7) );
					break;
				case 'M':
					nextDate.setMonth( nextDate.getMonth() + interval );
					break;
				case 'Y':
					nextDate.setFullYear( nextDate.getFullYear() + interval );
					break;
			}

			const nextDateString = nextDate.toISOString().slice( 0, 16 );

			// Get the original date from the data attribute
			const originalNextPaymentDate = scheduleForm.find( '#current_period_end' ).data( 'original-date' );

			// Show warning if next payment date was changed and original value exists
			if (originalNextPaymentDate && originalNextPaymentDate !== nextDateString) {
				const message = wp.i18n.__( 'Next payment date updated because billing period changed.', 'flexible-subscriptions' );
				clearMessage( 'current_period_end' );
				showMessage( 'current_period_end', message, 'warning' );
			}
			scheduleForm.find( '#current_period_end' ).val( nextDateString );
		}
	}
);
