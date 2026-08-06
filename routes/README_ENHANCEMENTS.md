# Route Optimizer - Complete Enhancement Documentation

## 🚀 Overview

The Route Optimizer has been comprehensively enhanced with enterprise-grade features including real-time traffic integration, advanced analytics, driver management, cost optimization, and a full REST API.

## 📋 Table of Contents

1. [New Features](#new-features)
2. [Database Schema](#database-schema)
3. [Models & Services](#models--services)
4. [API Documentation](#api-documentation)
5. [Installation & Setup](#installation--setup)
6. [Usage Guide](#usage-guide)
7. [Configuration](#configuration)

---

## 🎯 New Features

### 1. Driver Management System
- **Driver Profiles**: Complete driver information with licenses, certifications, and skills
- **Availability Tracking**: Calendar-based availability management
- **Performance Metrics**: Track deliveries, on-time percentage, and ratings
- **Skill-Based Assignment**: Automatic driver assignment based on required skills (hazmat, refrigerated, etc.)
- **Vehicle Assignment**: Link drivers to specific vehicles

### 2. Vehicle Management
- **Fleet Tracking**: Manage vehicles with detailed specifications
- **Fuel Efficiency**: Track fuel consumption and calculate costs
- **Maintenance Scheduling**: Schedule and track vehicle maintenance
- **Availability Management**: Real-time vehicle availability checking
- **GPS Integration**: Support for GPS device tracking

### 3. Analytics Dashboard
- **KPI Cards**: Real-time metrics for routes, distance, efficiency, and costs
- **Interactive Charts**: Trends, cost breakdown, algorithm performance
- **Driver Leaderboard**: Performance-based rankings
- **Predictive Analytics**: Forecast future route demands
- **Carbon Emissions Tracking**: Environmental impact monitoring

### 4. Route Templates
- **Reusable Patterns**: Save common routes as templates
- **Quick Application**: Apply templates to create new routes instantly
- **Template Library**: Public and private template sharing
- **Category Organization**: Organize templates by type/purpose
- **Usage Tracking**: Monitor template popularity

### 5. Traffic Integration
- **Real-Time Traffic**: Google Maps and Mapbox integration
- **Traffic-Aware Routing**: Optimize routes considering current traffic
- **Intelligent Caching**: Reduce API calls with smart caching
- **Traffic Levels**: Visual indication of traffic conditions
- **ETA Updates**: Live estimated time of arrival

### 6. Cost Management
- **Comprehensive Costing**: Fuel, driver, maintenance, and additional costs
- **Cost Breakdown**: Detailed analysis by cost type
- **Budget Tracking**: Monitor costs against budgets
- **Savings Calculator**: Compare optimized vs unoptimized routes
- **ROI Analysis**: Calculate return on investment

### 7. REST API
- **Full CRUD Operations**: Complete programmatic access
- **Authentication**: API key and session-based auth
- **Multiple Endpoints**: Routes, drivers, vehicles, analytics, templates
- **Traffic Data**: Real-time traffic information
- **Optimization Engine**: Remote route optimization

---

## 🗄️ Database Schema

### New Tables

#### `route_drivers`
Stores driver information and performance metrics.

```sql
- id, user_id, driver_code, license_number, license_expiry
- phone, email, status, rating
- total_deliveries, successful_deliveries, on_time_percentage
- preferred_vehicle_type, max_hours_per_day, hourly_rate
```

#### `route_driver_skills`
Driver certifications and special skills.

```sql
- id, driver_id, skill_type (hazmat, refrigerated, etc.)
- certification_number, certified_date, expiry_date, verified
```

#### `route_driver_availability`
Driver availability schedule.

```sql
- id, driver_id, date, start_time, end_time
- is_available, reason
```

#### `route_vehicles`
Vehicle fleet management.

```sql
- id, vehicle_code, license_plate, vehicle_type
- capacity_weight, capacity_volume, fuel_type, fuel_efficiency
- has_refrigeration, has_lift_gate, gps_device_id
- insurance_expiry, last_maintenance, next_maintenance
```

#### `route_performance`
Detailed route performance metrics.

```sql
- id, route_id, total_distance_km, total_duration_minutes
- total_stops, completed_stops, failed_stops
- fuel_cost, driver_cost, total_cost
- efficiency_score, customer_satisfaction, carbon_emissions_kg
```

#### `route_deliveries`
Individual delivery tracking.

```sql
- id, route_id, stop_id, customer_name, address
- delivery_type, priority, time_window_start, time_window_end
- estimated_arrival, actual_arrival, status
- signature_image_path, proof_of_delivery_path
- customer_rating, customer_feedback
```

#### `route_templates`
Reusable route patterns.

```sql
- id, name, description, created_by, is_public
- warehouse_locations (JSON), typical_stop_count
- days_of_week (JSON), frequency, use_count
```

#### `route_costs`
Additional route costs tracking.

```sql
- id, route_id, cost_type (fuel, driver, toll, etc.)
- amount, currency, description, recorded_at
```

#### `route_traffic_cache`
Traffic data caching.

```sql
- id, origin_lat, origin_lon, destination_lat, destination_lon
- distance_km, duration_minutes, traffic_duration_minutes
- traffic_level, data_source, cached_at, expires_at
```

---

## 📦 Models & Services

### Models

#### `Driver.php`
```php
// Create driver
$driver->createDriver($data);

// Get driver with metrics
$driver->getDriver($driverId);

// Manage skills
$driver->addSkill($driverId, $skillData);
$driver->hasSkill($driverId, 'hazmat');

// Availability
$driver->setAvailability($driverId, $date, $startTime, $endTime);
$driver->isAvailable($driverId, $date);

// Performance
$driver->updatePerformance($driverId, $metrics);
$driver->updateRating($driverId);
```

#### `Vehicle.php`
```php
// Create vehicle
$vehicle->createVehicle($data);

// Availability
$vehicle->isAvailable($vehicleId, $date);
$vehicle->getAvailableVehicles($date);

// Maintenance
$vehicle->scheduleMaintenance($vehicleId, $lastDate, $nextDate);
$vehicle->getMaintenanceDue(30); // Next 30 days

// Costs
$vehicle->calculateFuelCost($vehicleId, $distanceKm, $fuelPrice);
```

#### `RouteAnalytics.php`
```php
// Dashboard KPIs
$analytics->getDashboardKPIs($userId, $startDate, $endDate);

// Trends
$analytics->getRouteTrends('daily', 30);

// Leaderboard
$analytics->getDriverLeaderboard(10, 'efficiency');

// Cost analysis
$analytics->getCostBreakdown($startDate, $endDate);

// Predictions
$analytics->getPredictiveAnalytics(7); // Next 7 days
```

#### `RouteTemplate.php`
```php
// Create template
$template->createTemplate($data);
$template->addTemplateStops($templateId, $stops);

// Apply template
$template->applyTemplate($templateId, $routeName, $userId);

// Search
$template->searchTemplates($query, $userId);
$template->getPopularTemplates(10);
```

### Services

#### `TrafficService.php`
```php
// Initialize with provider
$traffic = new TrafficService('google', $apiKey);

// Get traffic data
$data = $traffic->getDistanceWithTraffic($originLat, $originLon, $destLat, $destLon);

// Traffic-optimized routing
$routes = $traffic->getOptimizedRoute($warehouse, $stops, $numVehicles);

// Route traffic conditions
$conditions = $traffic->getRouteTrafficConditions($routeId);
```

#### `CostCalculator.php`
```php
// Calculate costs
$calculator = new CostCalculator();
$costs = $calculator->calculateRouteCost($routeId);

// Estimate costs
$estimate = $calculator->estimateRouteCost($distanceKm, $durationMinutes, $vehicleId, $driverId);

// Compare routes
$comparison = $calculator->compareCosts([$routeId1, $routeId2]);

// Calculate savings
$savings = $calculator->calculateSavings($optimizedId, $unoptimizedId);
```

---

## 🔌 API Documentation

### Base URL
```
https://yourdomain.com/api/routes_api.php/
```

### Authentication
Include API key in header or query parameter:
```
X-API-Key: your_api_key_here
```
or
```
?api_key=your_api_key_here
```

### Endpoints

#### Routes

**GET /routes**
Get all routes for authenticated user.

**GET /routes/{id}**
Get specific route with stops.

**POST /routes**
Create new route.
```json
{
  "name": "Route Name",
  "warehouse_locations": [{"name": "Warehouse", "lat": 9.0, "lon": 38.7}],
  "driver_count": 2,
  "stops": [...]
}
```

**DELETE /routes/{id}**
Delete a route.

#### Optimize

**POST /optimize**
Optimize a route with traffic consideration.
```json
{
  "warehouse": {"lat": 9.0, "lon": 38.7, "name": "Main Warehouse"},
  "stops": [...],
  "num_vehicles": 2,
  "algorithm": "nearest_neighbor",
  "use_traffic": true
}
```

#### Drivers

**GET /drivers**
Get all active drivers.

**GET /drivers/{id}**
Get specific driver.

**POST /drivers**
Create new driver.

**PUT /drivers/{id}**
Update driver information.

#### Vehicles

**GET /vehicles**
Get all active vehicles.

**GET /vehicles/{id}**
Get specific vehicle.

**POST /vehicles**
Create new vehicle.

**PUT /vehicles/{id}**
Update vehicle information.

#### Analytics

**GET /analytics/dashboard?start_date=2024-01-01&end_date=2024-01-31**
Get dashboard KPIs.

**GET /analytics/trends?period=daily&limit=30**
Get route trends.

**GET /analytics/leaderboard?limit=10&metric=efficiency**
Get driver leaderboard.

**GET /analytics/costs?start_date=2024-01-01&end_date=2024-01-31**
Get cost breakdown.

**GET /analytics/predictions?days=7**
Get predictive analytics.

#### Templates

**GET /templates**
Get user templates.

**GET /templates/popular?limit=10**
Get popular templates.

**GET /templates/{id}**
Get specific template.

**POST /templates**
Create new template.

**POST /templates** (with apply_template)
Apply template to create route.
```json
{
  "apply_template": true,
  "template_id": 123,
  "route_name": "Monday Deliveries"
}
```

**DELETE /templates/{id}**
Delete template.

#### Traffic

**POST /traffic**
Get traffic data.
```json
{
  "origin": {"lat": 9.0, "lon": 38.7},
  "destination": {"lat": 9.1, "lon": 38.8}
}
```

or

```json
{
  "route_id": 123
}
```

---

## ⚙️ Installation & Setup

### 1. Database Setup

Run the enhancement schema:
```bash
mysql -u username -p database_name < sql/schema_route_enhancements.sql
```

### 2. Environment Configuration

Add to `.env`:
```env
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
MAPBOX_API_KEY=your_mapbox_api_key
```

### 3. File Permissions

Ensure upload directories exist:
```bash
mkdir -p uploads/exports
mkdir -p uploads/signatures
mkdir -p uploads/proof_of_delivery
chmod 755 uploads/*
```

### 4. Cron Jobs (Optional)

Clear expired traffic cache:
```bash
# Add to crontab
0 */6 * * * php /path/to/cron/clear_traffic_cache.php
```

---

## 📖 Usage Guide

### Creating a Driver

1. Navigate to Drivers section
2. Click "Add Driver"
3. Fill in driver details (license, contact, rates)
4. Add skills/certifications if needed
5. Set availability schedule

### Using Traffic-Aware Routing

1. Go to Route Optimizer
2. Enter warehouse and delivery addresses
3. Check "Use Traffic Data" option
4. Select optimization algorithm
5. Click "Optimize Routes"

### Viewing Analytics

1. Navigate to Analytics Dashboard
2. Select date range
3. View KPIs, trends, and charts
4. Export data as CSV if needed

### Creating Route Templates

1. Create and optimize a route
2. Click "Save as Template"
3. Name the template and add description
4. Set category and frequency
5. Mark as public to share with team

### Using the API

```javascript
// Example: Optimize route via API
fetch('https://yourdomain.com/api/routes_api.php/optimize', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'your_api_key'
  },
  body: JSON.stringify({
    warehouse: {lat: 9.0, lon: 38.7, name: "Main Warehouse"},
    stops: [...],
    num_vehicles: 2,
    use_traffic: true
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

---

## 🔧 Configuration

### Default Settings

Configure in `route_settings` table or via Settings page:

- `default_fuel_price_per_liter`: Default fuel price
- `default_driver_hourly_rate`: Default driver hourly rate
- `traffic_api_provider`: 'google' or 'mapbox'
- `enable_traffic_optimization`: Enable/disable traffic features
- `max_route_duration_hours`: Maximum route duration
- `default_vehicle_speed_kmh`: Default vehicle speed for estimates

### Traffic Cache Settings

In `TrafficService.php`:
```php
$traffic->setCacheDuration(900); // 15 minutes
$traffic->setCacheEnabled(true);
```

### Cost Calculation

In `CostCalculator.php`:
```php
$calculator->setFuelPrice(1.75); // Custom fuel price
$calculator->setDriverHourlyRate(20.00); // Custom driver rate
```

---

## 📊 Performance Metrics

The system tracks:

- **Route Efficiency**: 0-100 score based on optimization
- **On-Time Delivery**: Percentage of deliveries within time window
- **Cost Per Kilometer**: Total cost divided by distance
- **Driver Rating**: Composite score from multiple factors
- **Carbon Emissions**: Environmental impact tracking

---

## 🎓 Best Practices

1. **Regular Maintenance**: Update vehicle maintenance schedules
2. **Driver Training**: Keep driver certifications current
3. **Template Usage**: Use templates for recurring routes
4. **Traffic Monitoring**: Enable traffic for time-sensitive deliveries
5. **Cost Tracking**: Record all additional costs for accurate analysis
6. **Analytics Review**: Weekly review of performance metrics
7. **API Rate Limits**: Monitor API usage to avoid limits

---

## 🐛 Troubleshooting

### Traffic Data Not Loading
- Check API keys in `.env`
- Verify API quota limits
- Check traffic cache expiration

### Cost Calculations Incorrect
- Verify vehicle fuel efficiency settings
- Check driver hourly rates
- Review additional cost entries

### Driver Assignment Issues
- Check driver availability
- Verify required skills match
- Review vehicle assignments

---

## 📝 Changelog

### Version 2.0.0 (Current)
- Added driver management system
- Implemented vehicle fleet tracking
- Created analytics dashboard
- Added route templates
- Integrated traffic APIs
- Built comprehensive REST API
- Added cost calculator
- Implemented carbon tracking

---

## 🤝 Support

For issues or questions:
1. Check this documentation
2. Review API error messages
3. Check application logs
4. Contact system administrator

---

## 📄 License

Proprietary - All rights reserved
