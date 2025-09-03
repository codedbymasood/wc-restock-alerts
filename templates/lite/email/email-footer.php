<?php
/**
 * Email footer template.
 *
 * @package restock-alerts-for-woocommerce\template\email\
 * @author Store Boost Kit <storeboostkit@gmail.com>
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$footer_text = isset( $args['footer_text'] ) ? $args['footer_text'] : '';
?>
</div>
</td>
</tr>
</table>
<!-- End Content -->
</td>
</tr>
</table>
<!-- End Body -->
</td>
</tr>
<tr>
	<td align="center" valign="top">
		<?php echo wp_kses_post( wpautop( $footer_text ) ); ?>
	</td>
</tr>
</table>
</td>
</tr>
</table>
</div>
</body>
</html>