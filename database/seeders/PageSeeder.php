<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Starter text for the information pages linked from the footer.
 * It is a template: the owner should review it (ideally with a lawyer for
 * the policy pages) and edit it in Admin → Pages (Phase 4).
 * Existing pages are never overwritten.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => [$title, $description, $content]) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'meta_description' => $description,
                'content' => trim($content),
                'is_active' => true,
            ]);
        }
    }

    /** @return array<string, array{0:string,1:string,2:string}> */
    private function pages(): array
    {
        return [
            'about-us' => ['Our story', 'How our handmade chocolates are made in Kolkata.', <<<'HTML'
<p>We are a small chocolate kitchen in Kolkata. Every bar, truffle and bonbon is tempered, moulded, filled and wrapped by hand, a few trays at a time.</p>
<h2>How we work</h2>
<p>We use couverture chocolate — made with cocoa butter, not vegetable fat — and make our own fillings: caramel cooked in small pans, ganache with fresh cream, praline from nuts we roast ourselves. Seasonal flavours like nolen gur appear only while the ingredients are at their best.</p>
<h2>Made to order</h2>
<p>Because we work in small batches, most chocolates are made within a few days of reaching you. That's why our truffles taste fresh, and why some items sell out.</p>
<h2>Gifting and custom orders</h2>
<p>From a single thank-you box to wedding favours for 300 guests, we can personalise wrappers, tags and messages. Message us on WhatsApp with your occasion, quantity and date.</p>
HTML],

            'faq' => ['Frequently asked questions', 'Answers about delivery, storage, custom orders and payment.', <<<'HTML'
<details open><summary>Where do you deliver?</summary><p>We deliver across Kolkata ourselves and ship to the rest of India by courier. Enter your PIN code at checkout to see delivery options and charges.</p></details>
<details><summary>Can I get same-day delivery?</summary><p>Yes, in most Kolkata areas, when you order before the daily cut-off time shown at checkout. Orders after the cut-off are delivered the next available day.</p></details>
<details><summary>Will chocolates melt in transit?</summary><p>Courier orders are packed in insulated boxes with ice gel. In peak summer we may advise against shipping filled chocolates to very hot regions and suggest bars instead.</p></details>
<details><summary>How should I store chocolates?</summary><p>Keep them in a cool, dry place at 16–20 °C, away from sunlight. In hot weather, refrigerate in an airtight box and let them come to room temperature before eating. Truffles and bonbons are best within two weeks; bars within three months.</p></details>
<details><summary>Do you make custom or bulk orders?</summary><p>Yes — personalised wrappers, return gifts, corporate hampers and wedding favours. Please give us 5–7 days' notice for most custom orders, and more for large quantities.</p></details>
<details><summary>Are your chocolates vegetarian? Do they contain eggs?</summary><p>All our chocolates are vegetarian and contain no eggs. Allergen information is listed on every product page.</p></details>
<details><summary>How can I pay?</summary><p>We accept UPI and, where available, cash on delivery. Payment details are shown at checkout.</p></details>
HTML],

            'shipping-delivery-policy' => ['Shipping & delivery policy', 'Delivery areas, timelines and charges for Kolkata and outstation orders.', <<<'HTML'
<h2>Kolkata local delivery</h2>
<ul>
<li>We deliver to the Kolkata PIN codes listed at checkout. Charges depend on your area and are shown before you place the order.</li>
<li>Same-day delivery is available in selected areas for orders confirmed before the daily cut-off. Otherwise we deliver on the next available day.</li>
<li>Orders above the free-delivery amount for your area are delivered free.</li>
</ul>
<h2>Outside Kolkata</h2>
<ul>
<li>We ship across India through courier or India Post. Shipping charges depend on weight and destination and are confirmed by us before dispatch.</li>
<li>Estimated transit time is usually 3–7 working days after dispatch. Courier timelines are estimates and are not guaranteed.</li>
<li>We share the courier name and tracking number once your order is dispatched.</li>
</ul>
<h2>Receiving your order</h2>
<p>Please make sure someone is available at the address to receive the parcel, and refrigerate filled chocolates on arrival in warm weather. If a delivery fails because the address is incorrect or nobody is available, re-delivery may be charged.</p>
HTML],

            'cancellation-refund-policy' => ['Cancellation & refund policy', 'When orders can be cancelled and how refunds work.', <<<'HTML'
<h2>Cancellations</h2>
<ul>
<li>You can cancel a ready-stock order free of charge until we confirm it for preparation. Message us on WhatsApp with your order number.</li>
<li>Custom and personalised orders cannot be cancelled once preparation has started, because they are made specially for you.</li>
</ul>
<h2>Damaged or incorrect items</h2>
<p>If your order arrives damaged, melted or incorrect, send us photos of the box and products on WhatsApp within 24 hours of delivery. We will replace the items or refund their value.</p>
<h2>Refunds</h2>
<p>Approved refunds are made to the original UPI account within 5–7 working days. Because chocolates are perishable food, we cannot accept returns of opened or partly eaten products, or refund for a change of taste preference.</p>
HTML],

            'privacy-policy' => ['Privacy policy', 'What personal information we collect and how we use it.', <<<'HTML'
<p>This policy explains what information we collect when you use this website or order from us, and how we use it.</p>
<h2>What we collect</h2>
<ul>
<li>Your name, mobile number, email address (optional) and delivery address when you place an order or send an enquiry.</li>
<li>Payment references or screenshots you choose to upload to confirm a UPI payment. We do not collect or store card or bank login details.</li>
<li>Basic, anonymous usage data (such as pages visited) if analytics is enabled on this site.</li>
</ul>
<h2>How we use it</h2>
<ul>
<li>To prepare, deliver and support your orders, and to contact you about them by phone, SMS or WhatsApp.</li>
<li>To keep records required for accounting and tax purposes.</li>
</ul>
<p>We do not sell your information. We share it only with delivery partners who need it to deliver your order.</p>
<h2>Your choices</h2>
<p>You can ask us to update or delete your personal information at any time by contacting us. We may keep order records where the law requires us to.</p>
HTML],

            'terms-and-conditions' => ['Terms & conditions', 'Terms that apply when you use this website and order from us.', <<<'HTML'
<p>By using this website or placing an order, you agree to these terms.</p>
<h2>Orders and pricing</h2>
<ul>
<li>All prices are in Indian Rupees. Orders are confirmed only after we accept them and, for UPI orders, verify payment.</li>
<li>Handmade products can vary slightly in colour, shape and decoration from the photos.</li>
<li>We may decline or cancel an order, for example if an item is unavailable or a delivery address cannot be served. Any payment received for a cancelled order is refunded in full.</li>
</ul>
<h2>Allergens</h2>
<p>Our kitchen handles milk, soy, tree nuts, peanuts and gluten. Although ingredients are listed for each product, we cannot guarantee that any product is free from traces of these allergens.</p>
<h2>Liability</h2>
<p>We are not responsible for loss in quality caused by improper storage after delivery. Our liability for any order is limited to the amount paid for that order.</p>
<h2>Governing law</h2>
<p>These terms are governed by the laws of India, and disputes are subject to the jurisdiction of the courts in Kolkata, West Bengal.</p>
HTML],
        ];
    }
}
