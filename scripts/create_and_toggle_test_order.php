<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Component;
use App\Http\Controllers\OrderController;
use App\Http\Requests\OrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Pick a customer and product
$customer = Customer::first();
$product = Product::first();
if (! $customer || ! $product) { echo "Missing customer or product\n"; exit(1); }

// Pick a component
$comp = Component::first();
if (! $comp) { echo "No component found\n"; exit(1); }

// Create order
$order = Order::create([
    'customer_id' => $customer->id,
    'product_id' => $product->id,
    'base_price' => 100000,
    'final_price' => 100000,
    'status' => 'pending',
    'delivery_date' => date('Y-m-d'),
]);

// Attach component pivot qty=1 (no consumption yet)
$order->components()->attach($comp->id, ['quantity_used' => 1]);
$order->refresh()->load('components');

echo "Created order id={$order->id} with component id={$comp->id}\n";
foreach ($order->components as $c) echo sprintf("COMP - id=%d avail=%s stok_used=%s pivot=%s\n", $c->id, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');

// Print raw component DB values
$cdb = Component::find($comp->id);
echo sprintf("\nDB COMPONENT BEFORE: id=%d avail=%s stok_used=%s\n", $cdb->id, $cdb->quantity_available ?? 'N/A', $cdb->stok_used ?? 'N/A');

$ctl = new OrderController();
// Build an OrderRequest-like object for update() (needs route('order'))
$req = Request::create('/', 'POST', ['status' => 'disewa', 'customer_id' => $order->customer_id, 'product_id' => $order->product_id, 'delivery_date' => $order->delivery_date]);
$orderReq = new OrderRequest();
// Manually set container/route resolver so validation rules referencing route('order') can work
$orderReq->setContainer(app());
$orderReq->setMethod('POST');
$orderReq->initialize($req->query->all(), $req->request->all(), $req->attributes->all(), $req->cookies->all(), $req->files->all(), $req->server->all(), $req->getContent());
$orderReq->setRouteResolver(function () use ($order) { return new class($order) { private $o; public function __construct($o){$this->o=$o;} public function parameter($key=null, $default=null){ if($key===null) return ['order'=>$this->o]; if($key==='order') return $this->o; return $default; } public function parameters(){ return ['order'=>$this->o]; } }; });
$orderReq->setUserResolver(function() { return null; });
$orderReq->merge($req->request->all());
// set validator so validated() works
$validator = app('validator')->make($orderReq->all(), $orderReq->rules());
$orderReq->setValidator($validator);
$ctl->update($orderReq, $order);
$order->refresh()->load('components');

echo "\nAfter update() -> disewa:\n";
foreach ($order->components as $c) echo sprintf("COMP - id=%d avail=%s stok_used=%s pivot=%s\n", $c->id, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');

$cdb = Component::find($comp->id);
echo sprintf("DB COMPONENT AFTER DISewa: id=%d avail=%s stok_used=%s\n", $cdb->id, $cdb->quantity_available ?? 'N/A', $cdb->stok_used ?? 'N/A');

// Now edit to pending via update()
$req2 = Request::create('/', 'POST', ['status' => 'pending', 'customer_id' => $order->customer_id, 'product_id' => $order->product_id, 'delivery_date' => $order->delivery_date]);
$orderReq2 = new OrderRequest();
$orderReq2->setContainer(app());
$orderReq2->setMethod('POST');
$orderReq2->initialize($req2->query->all(), $req2->request->all(), $req2->attributes->all(), $req2->cookies->all(), $req2->files->all(), $req2->server->all(), $req2->getContent());
$orderReq2->setRouteResolver(function () use ($order) { return new class($order) { private $o; public function __construct($o){$this->o=$o;} public function parameter($key=null, $default=null){ if($key===null) return ['order'=>$this->o]; if($key==='order') return $this->o; return $default; } public function parameters(){ return ['order'=>$this->o]; } }; });
$orderReq2->merge($req2->request->all());
$validator2 = app('validator')->make($orderReq2->all(), $orderReq2->rules());
$orderReq2->setValidator($validator2);
$ctl->update($orderReq2, $order);
$order->refresh()->load('components');

echo "\nAfter update() -> pending:\n";
foreach ($order->components as $c) echo sprintf("COMP - id=%d avail=%s stok_used=%s pivot=%s\n", $c->id, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', isset($c->pivot)? $c->pivot->quantity_used : 'detached');

$cdb = Component::find($comp->id);
echo sprintf("DB COMPONENT AFTER PENDING: id=%d avail=%s stok_used=%s\n", $cdb->id, $cdb->quantity_available ?? 'N/A', $cdb->stok_used ?? 'N/A');

// Now update back to disewa via update()
$req3 = Request::create('/', 'POST', ['status' => 'disewa', 'customer_id' => $order->customer_id, 'product_id' => $order->product_id, 'delivery_date' => $order->delivery_date]);
$orderReq3 = new OrderRequest();
$orderReq3->setContainer(app());
$orderReq3->setMethod('POST');
$orderReq3->initialize($req3->query->all(), $req3->request->all(), $req3->attributes->all(), $req3->cookies->all(), $req3->files->all(), $req3->server->all(), $req3->getContent());
$orderReq3->setRouteResolver(function () use ($order) { return new class($order) { private $o; public function __construct($o){$this->o=$o;} public function parameter($key=null, $default=null){ if($key===null) return ['order'=>$this->o]; if($key==='order') return $this->o; return $default; } public function parameters(){ return ['order'=>$this->o]; } }; });
$orderReq3->merge($req3->request->all());
$validator3 = app('validator')->make($orderReq3->all(), $orderReq3->rules());
$orderReq3->setValidator($validator3);
$ctl->update($orderReq3, $order);
$order->refresh()->load('components');

echo "\nAfter update() -> disewa (again):\n";
foreach ($order->components as $c) echo sprintf("COMP - id=%d avail=%s stok_used=%s pivot=%s\n", $c->id, $c->quantity_available ?? 'N/A', $c->stok_used ?? 'N/A', $c->pivot->quantity_used ?? 'N/A');

$cdb = Component::find($comp->id);
echo sprintf("DB COMPONENT AFTER RE-DISEWA: id=%d avail=%s stok_used=%s\n", $cdb->id, $cdb->quantity_available ?? 'N/A', $cdb->stok_used ?? 'N/A');

echo "\nDone.\n";
