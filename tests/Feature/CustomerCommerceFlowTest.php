<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cart\Models\ShoppingCart;
use Modules\Category\Models\Category;
use Modules\Customer\Models\CustomerAddress;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductSku;
use Tests\TestCase;

class CustomerCommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_login_by_phone(): void
    {
        $registerResponse = $this->postJson('/api/storefront/register', [
            'name' => 'Customer One',
            'email' => 'customer@example.com',
            'phone' => '0900000001',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('data.role', User::ROLE_CUSTOMER)
            ->assertJsonPath('data.phone', '0900000001');

        auth()->logout();

        $this->postJson('/api/storefront/login', [
            'login' => '0900000001',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('data.email', 'customer@example.com');
    }

    public function test_guest_cart_can_checkout_and_snapshot_product_data(): void
    {
        $sku = $this->createPurchasableSku();

        $addResponse = $this->postJson('/api/storefront/cart/items', [
            'product_sku_id' => $sku->id,
            'quantity' => 2,
        ]);

        $addResponse->assertCreated()
            ->assertJsonPath('data.items.0.product_sku_id', $sku->id)
            ->assertJsonPath('data.items.0.quantity', 2);

        $guestCart = ShoppingCart::query()->firstOrFail();

        $placeOrderResponse = $this
            ->withUnencryptedCookie(
                config('cart.guest_cookie', 'cmbcore_guest_cart'),
                $guestCart->guest_token
            )
            ->postJson('/api/storefront/checkout/place-order', [
            'mode' => 'cart',
            'customer_name' => 'Guest Buyer',
            'customer_phone' => '0900000002',
            'guest_email' => 'guest@example.com',
            'recipient_name' => 'Guest Buyer',
            'shipping_phone' => '0900000002',
            'province' => 'HCM',
            'district' => 'District 1',
            'ward' => 'Ben Nghe',
            'address_line' => '1 Nguyen Hue',
        ]);

        $placeOrderResponse->assertCreated()
            ->assertJsonPath('data.user_id', null)
            ->assertJsonPath('data.source', Order::SOURCE_GUEST)
            ->assertJsonPath('data.items.0.product_name', 'Commerce Product')
            ->assertJsonPath('data.items.0.quantity', 2);

        $orderId = (int) $placeOrderResponse->json('data.id');

        ProductSku::query()->whereKey($sku->id)->update(['price' => 990000]);

        $order = Order::query()->with('items')->findOrFail($orderId);
        self::assertSame('249000.00', $order->items->first()->unit_price);
    }

    public function test_authenticated_customer_can_save_address_and_place_order(): void
    {
        $sku = $this->createPurchasableSku();
        $customer = User::query()->create([
            'name' => 'Customer Two',
            'email' => 'customer-two@example.com',
            'phone' => '0900000003',
            'password' => 'password123',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer);

        $this->postJson('/api/storefront/addresses', [
            'label' => 'Home',
            'recipient_name' => 'Customer Two',
            'phone' => '0900000003',
            'province' => 'Hanoi',
            'district' => 'Ba Dinh',
            'ward' => 'Ngoc Ha',
            'address_line' => '2 Doi Can',
            'is_default' => true,
        ])->assertCreated();

        $address = CustomerAddress::query()->where('user_id', $customer->id)->firstOrFail();

        $this->postJson('/api/storefront/cart/items', [
            'product_sku_id' => $sku->id,
            'quantity' => 1,
        ])->assertCreated();

        $this->postJson('/api/storefront/checkout/place-order', [
            'mode' => 'cart',
            'customer_name' => 'Customer Two',
            'customer_phone' => '0900000003',
            'address_id' => $address->id,
            'recipient_name' => 'Ignore because saved address selected',
            'shipping_phone' => '0000000000',
            'address_line' => 'Ignore',
        ])->assertCreated()
            ->assertJsonPath('data.user_id', $customer->id)
            ->assertJsonPath('data.source', Order::SOURCE_ACCOUNT)
            ->assertJsonPath('data.shipping_recipient_name', 'Customer Two')
            ->assertJsonPath('data.shipping_phone', '0900000003');
    }

    private function createPurchasableSku(): ProductSku
    {
        $category = Category::query()->create([
            'name' => 'Commerce Category',
            'slug' => 'commerce-category',
            'level' => 1,
            'status' => Category::STATUS_ACTIVE,
        ]);

        $product = Product::query()->create([
            'name' => 'Commerce Product',
            'slug' => 'commerce-product',
            'status' => Product::STATUS_ACTIVE,
            'type' => Product::TYPE_SIMPLE,
            'category_id' => $category->id,
        ]);

        return ProductSku::query()->create([
            'product_id' => $product->id,
            'name' => 'Default SKU',
            'sku_code' => 'COMMERCE-001',
            'price' => 249000,
            'stock_quantity' => 10,
            'status' => ProductSku::STATUS_ACTIVE,
        ]);
    }
}
