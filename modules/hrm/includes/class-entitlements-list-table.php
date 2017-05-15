<?php
namespace WeDevs\ERP\HRM;

/**
 * List table class
 */
class Entitlement_List_Table extends \WP_List_Table {

    function __construct() {
        global $status, $page;

        parent::__construct( array(
            'singular' => 'entitlement',
            'plural'   => 'entitlements',
            'ajax'     => false
        ) );
    }

    function get_table_classes() {
        return array( 'widefat', 'fixed', 'striped', 'entitlement-list-table', $this->_args['plural'] );
    }

    /**
     * Extra filters for the list table
     *
     * @since 0.1
     *
     * @param string $which
     *
     * @return void
     */
    function extra_tablenav( $which ) {
        if ( $which != 'top' ) {
            return;
        }

        $date_ranges = $this->get_date_ranges();
        $from_date   = date( 'Y-m-d', strtotime( $date_ranges['from_date'] ) );
        $to_date     = date( 'Y-m-d', strtotime( $date_ranges['to_date'] ) );
        ?>
        <div class="alignleft actions">
            <label><?php _e( 'From', 'erp' ) ?> <input type="text" class="erp-date-field" name="from" value="<?php echo $from_date; ?>"></label>
            <label><?php _e( 'To', 'erp' ) ?> <input type="text" class="erp-date-field" name="to" value="<?php echo $to_date; ?>"></label>
            <?php submit_button( __( 'Filter' ), 'button', 'filter_entitlement', false ); ?>
        </div>
        <?php

    }


    /**
     * Message to show if no entitlement found
     *
     * @return void
     */
    function no_items() {
        _e( 'No entitlement found.', 'erp' );
    }

    /**
     * Default column values if no callback found
     *
     * @param  object  $item
     * @param  string  $column_name
     *
     * @return string
     */
    function column_default( $entitlement, $column_name ) {
        $balance   = erp_hr_leave_get_balance( $entitlement->user_id );

        if ( isset( $balance[ $entitlement->policy_id ] ) ) {
            $scheduled = $balance[ $entitlement->policy_id ]['scheduled'];
            $available = $balance[ $entitlement->policy_id ]['entitlement'] - $balance[ $entitlement->policy_id ]['total'];
        } else {
            $scheduled = '';
            $available = '';
        }

        switch ( $column_name ) {
            case 'name':
                return sprintf( '<strong><a href="%s">%s</a></strong>', erp_hr_url_single_employee( $entitlement->user_id ), esc_html( $entitlement->employee_name ) );

            case 'leave_policy':
                return esc_html( $entitlement->policy_name );

            case 'valid_from':
                return erp_format_date( $entitlement->from_date );

            case 'valid_to':
                return erp_format_date( $entitlement->to_date );

            case 'days':
                return number_format_i18n( $entitlement->days );

            case 'scheduled':
                return $scheduled ? sprintf( __( '%d days', 'erp' ), number_format_i18n( $scheduled ) ) : '-';

            case 'available':
                if ( $available < 0 ) {
                    return sprintf( '<span class="red">%d %s</span>', number_format_i18n( $available ), __( 'days', 'erp' ) );
                } elseif ( $available > 0 ) {
                    return sprintf( '<span class="green">%d %s</span>', number_format_i18n( $available ), __( 'days', 'erp' ) );
                } elseif ( $available === 0 ) {
                    return sprintf( '<span class="gray">%d %s</span>', 0, __( 'days', 'erp' ) );
                } else {
                    return sprintf( '<span class="green">%d %s</span>', number_format_i18n( $entitlement->days ), __( 'days', 'erp' ) );
                }

            default:
                return isset( $entitlement->$column_name ) ? $entitlement->$column_name : '';
        }
    }

    /**
     * Get the column names
     *
     * @return array
     */
    function get_columns() {
        $columns = array(
            'cb'           => '<input type="checkbox" />',
            'name'         => __( 'Employee Name', 'erp' ),
            'leave_policy' => __( 'Leave Policy', 'erp' ),
            'valid_from'   => __( 'Valid From', 'erp' ),
            'valid_to'     => __( 'Valid To', 'erp' ),
            'days'         => __( 'Days', 'erp' ),
            'scheduled'    => __( 'Scheduled', 'erp' ),
            'available'    => __( 'available', 'erp' )
        );

        return apply_filters( 'erp_hr_entitlement_table_cols', $columns );
    }

    /**
     * Render the designation name column
     *
     * @param  object  $item
     *
     * @return string
     */
    function column_name( $entitlement ) {

        $actions           = array();
        $delete_url        = '';

        if ( erp_get_option( 'erp_debug_mode', 'erp_settings_general', 0 ) ) {
            $actions['delete'] = sprintf( '<a href="%s" class="submitdelete" data-id="%d" data-user_id="%d" data-policy_id="%d" title="%s">%s</a>', $delete_url, $entitlement->id, $entitlement->user_id, $entitlement->policy_id, __( 'Delete this item', 'erp' ), __( 'Delete', 'erp' ) );
        }

        return sprintf( '<a href="%3$s"><strong>%1$s</strong></a> %2$s', esc_html( $entitlement->employee_name ), $this->row_actions( $actions ), erp_hr_url_single_employee( $entitlement->user_id ) );
    }

    /**
     * Trigger current action
     *
     * @return string
     */
    public function current_action() {

        if ( isset( $_REQUEST['filter_entitlement'] ) ) {
            return 'filter_entitlement';
        }

        return parent::current_action();
    }

    /**
     * Get sortable columns
     *
     * @return array
     */
    function get_sortable_columns() {
        $sortable_columns = array(
            'name' => array( 'name', true ),
        );

        return $sortable_columns;
    }

    /**
     * Set the bulk actions
     *
     * @return array
     */
    function get_bulk_actions() {
        $actions = array(
            'entitlement_delete'  => __( 'Delete', 'erp' ),
        );
        return $actions;
    }

    /**
     * Render the checkbox column
     *
     * @param  object  $item
     *
     * @return string
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="entitlement_id[]" value="%s" />', $item->id
        );
    }

    /**
     * Prepare the class items
     *
     * @since 0.1
     * @since 1.2.0 Using `erp_get_financial_year_dates` for financial start and end dates
     *
     * @return void
     */
    function prepare_items() {

        $columns               = $this->get_columns();
        $hidden                = array( );
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page              = 20;
        $current_page          = $this->get_pagenum();
        $offset                = ( $current_page -1 ) * $per_page;
        $this->page_status     = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '2';

        $args = [
            'offset' => $offset,
            'number' => $per_page,
        ];

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = 'u.display_name';
            $args['order']   = $_REQUEST['order'] ;
        }

        $date_ranges = $this->get_date_ranges();

        $args['from_date'] = $date_ranges['from_date'];
        $args['to_date'] = $date_ranges['to_date'];

        $this->items  = erp_hr_leave_get_entitlements( $args );

        $this->set_pagination_args( array(
            'total_items' => erp_hr_leave_count_entitlements( $args ),
            'per_page'    => $per_page
        ) );
    }

    /**
     * Get date range
     *
     * @since 1.2.0
     *
     * @return array
     */
    private function get_date_ranges() {
        $financial_year_dates = erp_get_financial_year_dates();

        $from_date  = $financial_year_dates['start'];
        $to_date    = $financial_year_dates['end'];

        if ( ! empty( $_GET['from'] ) ) {
            $from_date = date( 'Y-m-d 00:00:00', strtotime( $_GET['from'] ) );
        }

        if ( ! empty( $_GET['to'] ) ) {
            $to_date = date( 'Y-m-d 23:59:59', strtotime( $_GET['to'] ) );
        }

        return [
            'from_date' => $from_date,
            'to_date'   => $to_date
        ];
    }
}
