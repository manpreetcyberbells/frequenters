<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Food & Beverage' => [
                'Restaurants' => ['Snacks', 'Main Course', 'Beverages'],
                'Cafés & Coffee Shops' => ['Coffee', 'Pastries'],
                'Bars & Pubs' => ['Cocktails', 'Beer', 'Wine'],
                'Bakeries & Dessert Shops' => ['Cakes', 'Cookies', 'Pastries'],
                'Food Trucks' => ['Fast Food', 'Street Snacks'],
                'Specialty Food Stores' => ['Organic', 'Gourmet', 'Imported Goods'],
            ],
            'Retail & Shopping' => [
                'Clothing & Apparel Stores' => ['Men', 'Women', 'Kids'],
                'Shoe & Accessories Shops' => ['Shoes', 'Bags', 'Jewelry'],
                'Electronics & Tech Stores' => ['Mobile Phones', 'Computers', 'Accessories'],
                'Bookstores' => ['Fiction', 'Non-Fiction', 'Children'],
                'Furniture & Home Decor Stores' => ['Living Room', 'Bedroom', 'Outdoor'],
                'Grocery & Supermarkets' => ['Fresh Produce', 'Packaged Foods', 'Dairy'],
                'Thrift & Vintage Shops' => ['Clothing', 'Furniture', 'Decor'],
                'Beauty & Cosmetics Stores' => ['Makeup', 'Skincare', 'Haircare'],
            ],
            'Health & Wellness' => [
                'Gyms & Fitness Centers' => ['Cardio', 'Strength Training', 'Personal Training'],
                'Yoga & Pilates Studios' => ['Yoga Classes', 'Pilates Classes'],
                'Spas & Massage Therapy Centers' => ['Massage', 'Facials', 'Body Treatments'],
                'Chiropractors & Wellness Clinics' => ['Chiropractic', 'Acupuncture'],
                'Health Food & Supplement Stores' => ['Vitamins', 'Organic Foods'],
                'Mental Health & Counseling Services' => ['Therapy', 'Support Groups'],
            ],
            'Entertainment & Leisure' => [
                'Movie Theaters' => ['2D', '3D', 'IMAX'],
                'Concert Venues' => ['Indoor', 'Outdoor'],
                'Bowling Alleys & Arcades' => ['Bowling', 'Arcade Games'],
                'Escape Rooms' => ['Horror', 'Mystery'],
                'Amusement & Theme Parks' => ['Roller Coasters', 'Water Rides'],
                'Gaming Lounges & VR Arcades' => ['PC Gaming', 'VR'],
                'Nightclubs & Live Music Venues' => ['DJ', 'Live Bands'],
                'Art Galleries & Museums' => ['Modern Art', 'Historical'],
            ],
            'Hospitality & Travel' => [
                'Hotels & Resorts' => ['Luxury', 'Budget'],
                'Bed & Breakfasts' => ['Romantic', 'Countryside'],
                'Hostels & Short-Term Rentals' => ['Shared Rooms', 'Private Rentals'],
                'Travel Agencies & Tour Operators' => ['Domestic', 'International'],
                'Car Rentals & Transportation Services' => ['Self Drive', 'With Driver'],
            ],
            'Personal Services' => [
                'Salons & Barbershops' => ['Haircut', 'Shave', 'Styling'],
                'Nail & Beauty Salons' => ['Nail Art', 'Manicure', 'Pedicure'],
                'Tailors & Dry Cleaners' => ['Tailoring', 'Dry Cleaning'],
                'Pet Grooming & Boarding Services' => ['Bathing', 'Boarding'],
            ],
            'Professional Services' => [
                'Co-Working Spaces & Offices' => ['Hot Desk', 'Private Office'],
                'Printing & Copy Services' => ['Digital Printing', 'Large Format'],
                'Legal & Financial Consulting' => ['Legal Advice', 'Tax Filing'],
                'Marketing & Advertising Agencies' => ['SEO', 'Social Media', 'Branding'],
            ],
            'Education & Learning' => [
                'Language Schools' => ['English', 'Spanish', 'French'],
                'Music & Dance Studios' => ['Guitar', 'Piano', 'Ballet', 'Hip-Hop'],
                'Tutoring & Test Prep Centers' => ['SAT', 'GRE', 'High School'],
                'Cooking & Craft Classes' => ['Baking', 'Painting', 'Handicrafts'],
            ],
            'Events & Experiences' => [
                'Wedding & Event Planning Services' => ['Weddings', 'Corporate Events'],
                'Party Supply & Rental Stores' => ['Decorations', 'Furniture Rental'],
                'Conference & Meeting Venues' => ['Corporate', 'Community'],
                'Photography & Videography Services' => ['Weddings', 'Portraits', 'Corporate Shoots'],
            ],
            'Automotive & Transportation' => [
                'Car Dealerships' => ['New Cars', 'Used Cars'],
                'Auto Repair & Maintenance Shops' => ['Engine Repair', 'Tire Service'],
                'Gas Stations & Car Washes' => ['Fuel', 'Car Wash'],
                'Bike & Scooter Rental Services' => ['Electric', 'Manual'],
            ]
        ];


        foreach ($categories as $parentName => $subCats) {
            $parent = Category::create(['name' => $parentName]);

            foreach ($subCats as $childName => $subSubCats) {
                $child = Category::create(['name' => $childName, 'parent_id' => $parent->id]);

                foreach ($subSubCats as $subSubName) {
                    Category::create(['name' => $subSubName, 'parent_id' => $child->id]);
                }
            }
        }
    }
}

