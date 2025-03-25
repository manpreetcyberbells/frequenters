# Frequenters Database Schema

## 1. users

Columns:

user_id: Primary identifier for a user.

name: Full name of the user (business name for businesses).

email: Email address, used for login.

password: Encrypted password.

role: Specifies user type (e.g., customer, business, admin).

last_login_at: Timestamp of the last login activity.

created_at: Timestamp when the user was created.

updated_at: Timestamp when the user information was last updated.

Use Case:

Manage user authentication.

Distinguish between different roles (e.g., customer or business).

## 2. customer_profiles

Columns:

customer_id: Foreign key linked to users.user_id.

address_line1: Address details for customer communication.

address_line2: Additional address details (optional).

city: City of the customer.

state: State of residence.

country: Country of residence.

zip_code: Zip code for location-based searches.

radius: Search radius for nearby businesses (in miles).

preferences: JSON field storing customer preferences, like notifications.

Use Case:

Store and personalize customer profile details.

Enable location-based services and notifications.

## 3. business_profiles

Columns:

business_id: Foreign key linked to users.user_id.

industry_type: Industry the business operates in (e.g., restaurant, cafe).

address_line1: Business address for localization.

address_line2: Additional business address details (optional).

city: City where the business is located.

state: State of the business.

country: Country of the business.

zip_code: Zip code for business search.

website: URL to the business’s official site.

description: Short description of the business.

photos: JSON array containing URLs to business images.

hours: JSON storing business operation hours (e.g., { "mon": "9:00-17:00" }).

points_available: Points available for rewards and check-ins.

points_per_checkin: Points allocated per customer check-in.

status: Indicates the business's operational status (active, inactive).

Use Case:

Manage business information for display to customers.

Track and manage available points for reward and check-in functionality.

## 4. customer_business_relationships

Columns:

customer_id: Foreign key linked to users.user_id.

business_id: Foreign key linked to users.user_id.

interaction_count: Number of times the customer has interacted with the business.

last_interaction_date: Timestamp of the last interaction.

total_points_earned: Total points the customer has earned at this business.

customer_tier: Tier level (e.g., new => Never Visited, regular => Repeat, daily => Frequenters).

Use Case:

Track customer interactions and loyalty with businesses.

Allow businesses to segment customers based on tiers.

## 5. rewards

Columns:

reward_id: Primary identifier for rewards.

business_id: Foreign key linked to users.user_id.

reward_name: Name of the reward.

points_required: Points needed to redeem the reward.

valid_from: Start date of the reward.

valid_until: Expiry date of the reward.

usage_limit: Maximum redemptions allowed per reward.

Use Case:

Allow businesses to define rewards for customers.

Control reward validity and availability.

## 6. reward_redemptions

Columns:

redemption_id: Primary identifier for redemptions.

customer_id: Foreign key linked to users.user_id.

reward_id: Foreign key linked to rewards.reward_id.

business_id: Foreign key linked to users.user_id.

points_redeemed: Points deducted from the customer’s balance.

status: Current status of the redemption (pending, success, failed).

redemption_code: Unique code generated for each redemption.

Use Case:

Record and track reward redemption activities.

Enable businesses to validate and process customer reward redemptions.

## 7. reward_conditions

Columns:

condition_id: Primary identifier for conditions.

reward_id: Foreign key linked to rewards.reward_id.

condition_type: Type of condition (e.g., min_visits, specific_tier).

condition_value: Value associated with the condition.

Use Case:

Allow businesses to apply conditions to rewards (e.g., minimum visits or specific customer tiers).

Ensure rewards are redeemed under set conditions.

## 8. transactions

Columns:

transaction_id: Primary identifier for transactions.

business_id: Foreign key linked to users.user_id.

customer_id: Foreign key linked to users.user_id.

transaction_date: Timestamp of the transaction.

points: Points involved in the transaction.

description: Brief description of the transaction.

Use Case:

Log point transfers between businesses and customers.

Provide detailed transaction records for audits and analytics.

The remaining schema details will be continued in the next document.

## 9. checkins

Columns:

checkin_id: Primary identifier for check-ins.

business_id: Foreign key linked to users.user_id.

customer_id: Foreign key linked to users.user_id.

checkin_date: Timestamp of the check-in.

points_earned: Points awarded to the customer for the check-in.

Use Case:

Log customer check-ins at businesses.

Award points based on business-specific settings.

## 10. points

Columns:

point_id: Primary identifier for point transactions.

business_id: Foreign key linked to users.user_id.

customer_id: Foreign key linked to users.user_id.

points_earned: Points credited to a customer.

points_deducted: Points debited from a customer.

description: Reason for the transaction (e.g., check-in, reward redemption).

created_at: Timestamp of the transaction.

Use Case:

Track all point transactions between businesses and customers.

Provide a detailed history of point activity.

## 11. geo_locations

Columns:

location_id: Primary identifier for locations.

business_id: Foreign key linked to users.user_id.

latitude: Latitude coordinate of the business.

longitude: Longitude coordinate of the business.

radius: Service radius in miles.

Use Case:

Enable geo-location-based business searches for customers.

Define the operational service radius for businesses.

## 12. notifications

Columns:

notification_id: Primary identifier for notifications.

user_id: Foreign key linked to users.user_id.

title: Title of the notification.

message: Detailed notification content.

status: Current status (unread, read).

created_at: Timestamp when the notification was created.

Use Case:

Deliver personalized notifications to customers and businesses.

Allow users to track and manage their notifications.

## 13. industries

Columns:

industry_id: Primary identifier for industries.

industry_name: Name of the industry (e.g., restaurant, salon, retail).

Use Case:

Provide a predefined list of industries for business categorization.

Enable customers to search businesses by industry type.

## 14. activity_logs

Columns:

log_id: Primary identifier for activity logs.

user_id: Foreign key linked to users.user_id.

action: Description of the activity (e.g., login, reward redemption).

created_at: Timestamp of the activity.

Use Case:

Maintain a record of user activities for security and audit purposes.

Analyze customer and business behavior patterns.

## 15. challenges

Columns:

challenge_id: Primary identifier for challenges.

name: Name of the challenge.

description: Details of the challenge.

criteria: JSON field specifying challenge completion criteria.

reward_points: Points awarded upon challenge completion.

valid_from: Start date for the challenge.

valid_until: End date for the challenge.

Use Case:

Allow businesses to engage customers with challenges (e.g., Visit 5 times this month).

Reward customers for completing challenges.

## 16. referrals

Columns:

referral_id: Primary identifier for referrals.

referrer_id: Foreign key linked to users.user_id (customer who referred).

referred_id: Foreign key linked to users.user_id (new customer).

reward_points: Points earned by the referrer for the referral.

status: Current status of the referral (pending, completed).

Use Case:

Track and manage customer referrals.

Encourage customer engagement through rewards for referrals.

## 17. settings

Columns:

setting_id: Primary identifier for settings.

key: Name of the setting (e.g., default_points_per_checkin).

value: Value of the setting (e.g., 10).

Use Case:

Centralize global platform settings.

Allow administrators to modify default behavior across the platform.