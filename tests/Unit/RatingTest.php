<?php


namespace Tests\Unit;

use App\Events\ModelRated;
use App\Events\ModelUnrated;
use App\Product;
use App\Rating;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_belongs_to_many_users()
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Product $product */
        $product = factory(Product::class)->create();

        $user->rate($product, 5);

        // dd($user->ratings()->get());
        // dd($product->ratings()->get())

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->ratings(Product::class)->get());
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $product->qualifiers(User::class)->get());
    }

    public function test_averageRating()
    {
        Event::fake();

        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var User $user2 */
        $user2 = factory(User::class)->create();
        /** @var Product $product */
        $product = factory(Product::class)->create();

        $user->rate($product, 5);
        $user2->rate($product, 10);
        $user->rate($user, 5);

        $this->assertEquals(7.5, $product->averageRating(User::class));
        $this->assertEquals(5, $user->averageRating(User::class));
    }

    public function test_rating_model()
    {
        Event::fake();


        $user = factory(User::class)->create();

        $user2 = factory(User::class)->create();

        $product = factory(Product::class)->create();

        $user->rate($product, 5);
        $user->rate($user2, 3);


        $rating = Rating::first();
        $rating2 = Rating::get()->last();

        $this->assertInstanceOf(Product::class, $rating->rateable);
        $this->assertInstanceOf(User::class, $rating->qualifier);
        $this->assertEquals($user->id, $rating->qualifier->id);
        $this->assertEquals($product->id, $rating->rateable->id);

        $this->assertInstanceOf(User::class, $rating2->rateable);
        $this->assertInstanceOf(User::class, $rating2->qualifier);
        $this->assertEquals($user->id, $rating2->qualifier->id);
        $this->assertEquals($user2->id, $rating2->rateable->id);

        //        Event::assertDispatchedTimes(ModelRated::class);
    }

    public function test_unrate_product()
    {
        Event::fake();

        /** @var User $user */
        $user = factory(User::class)->create();
        /** @var Product $product */
        $product = factory(Product::class)->create();

        $user->rate($product, 5);
        $result = $user->unrate($product);

        $this->assertIsBool($result);
        $this->assertTrue($result);

        Event::assertDispatchedTimes(ModelUnrated::class);
    }

    public function test_unrate_product_has_not_rate()
    {
        Event::fake();
        $user = factory(User::class)->create();
        $product = factory(Product::class)->create();

        $result = $user->unrate($product);

        $this->assertIsBool($result);
        $this->assertFalse($result);


        // $this->assertEquals(0, $product->qualifications()->count());

        //Event::assertNotDispatched(ModelUnrated::class);
    }
}