# Route Optimizer - Quick Start Guide

## 🚀 Get Started in 5 Minutes!

### Step 1: Install Enhancements (2 minutes)

Run the installation script:

```bash
cd c:\xampp\htdocs\stock_management\routes
php install_enhancements.php
```

This will:
- ✅ Create all database tables
- ✅ Set up required directories
- ✅ Configure default settings
- ✅ Verify installation

### Step 2: Configure API Keys (1 minute)

Edit `.env` file and add your API keys:

```env
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
MAPBOX_API_KEY=your_mapbox_api_key_here
```

**Don't have API keys?** The system will work without them using fallback calculations.

### Step 3: Access the Dashboard (30 seconds)

Open your browser and navigate to:

```
http://localhost/stock_management/routes/analytics.php
```

You'll see:
- 📊 KPI cards with key metrics
- 📈 Interactive charts
- 🏆 Driver leaderboard
- 💰 Cost breakdowns

### Step 4: Create Your First Optimized Route (1 minute)

1. Go to: `http://localhost/stock_management/routes/index.php`
2. Enter your warehouse location
3. Add delivery addresses (one per line)
4. Select number of drivers
5. Check "Use Traffic Data" for real-time optimization
6. Click "Optimize Routes"

### Step 5: Explore Features (30 seconds)

**Driver Management**:
- Add drivers with skills and certifications
- Set availability schedules
- Track performance metrics

**Vehicle Management**:
- Add vehicles with specifications
- Track fuel efficiency
- Schedule maintenance

**Templates**:
- Save common routes as templates
- Quick-apply for recurring deliveries
- Share templates with team

**API Access**:
- Use REST API for integrations
- Full programmatic control
- Real-time data access

---

## 📱 Common Tasks

### Add a Driver

```php
require_once 'models/Driver.php';

$driver = new Driver();
$driverId = $driver->createDriver([
    'user_id' => 1,
    'driver_code' => 'DRV001',
    'license_number' => 'ABC123',
    'phone' => '+1234567890',
    'email' => 'driver@example.com',
    'hourly_rate' => 15.00
]);

// Add hazmat certification
$driver->addSkill($driverId, [
    'skill_type' => 'hazmat',
    'certification_number' => 'HAZ-2024-001'
]);
```

### Add a Vehicle

```php
require_once 'models/Vehicle.php';

$vehicle = new Vehicle();
$vehicleId = $vehicle->createVehicle([
    'vehicle_code' => 'VAN001',
    'license_plate' => 'ABC-123',
    'vehicle_type' => 'van',
    'capacity_weight' => 1000, // kg
    'fuel_type' => 'diesel',
    'fuel_efficiency' => 12, // km per liter
    'cost_per_km' => 0.15
]);
```

### Optimize Route with Traffic

```php
require_once 'services/TrafficService.php';

$traffic = new TrafficService('google');
$routes = $traffic->getOptimizedRoute(
    ['lat' => 9.0, 'lon' => 38.7, 'name' => 'Warehouse'],
    $deliveryStops,
    2 // number of vehicles
);
```

### Calculate Route Costs

```php
require_once 'lib/CostCalculator.php';

$calculator = new CostCalculator();
$costs = $calculator->calculateRouteCost($routeId);

echo "Total Cost: $" . $costs['total_cost'];
echo "Fuel: $" . $costs['fuel_cost'];
echo "Driver: $" . $costs['driver_cost'];
echo "CO2: " . $costs['carbon_emissions_kg'] . " kg";
```

### Use API

```bash
# Get all routes
curl -H "X-API-Key: your_key" \
  http://localhost/stock_management/api/routes_api.php/routes

# Optimize route
curl -X POST \
  -H "X-API-Key: your_key" \
  -H "Content-Type: application/json" \
  -d '{
    "warehouse": {"lat": 9.0, "lon": 38.7, "name": "Main"},
    "stops": [...],
    "num_vehicles": 2,
    "use_traffic": true
  }' \
  http://localhost/stock_management/api/routes_api.php/optimize

# Get analytics
curl -H "X-API-Key: your_key" \
  "http://localhost/stock_management/api/routes_api.php/analytics/dashboard?start_date=2024-01-01&end_date=2024-01-31"
```

---

## 🎯 Key Features

### 1. Real-Time Traffic ⚡
- Google Maps integration
- Mapbox support
- Intelligent caching
- Live ETA updates

### 2. Analytics Dashboard 📊
- KPI cards
- Interactive charts
- Driver leaderboard
- Cost analysis
- Predictions

### 3. Cost Optimization 💰
- Fuel costs
- Driver costs
- Maintenance tracking
- ROI calculations
- Savings analysis

### 4. Driver Management 👥
- Skills & certifications
- Availability scheduling
- Performance tracking
- Rating system

### 5. Vehicle Fleet 🚗
- Fleet tracking
- Fuel efficiency
- Maintenance scheduling
- Utilization stats

### 6. Route Templates 📋
- Save common routes
- Quick-apply
- Template library
- Usage tracking

### 7. REST API 🔌
- Full CRUD operations
- Authentication
- Real-time data
- Easy integration

---

## 💡 Pro Tips

1. **Enable Traffic**: Always use traffic data for time-sensitive deliveries
2. **Use Templates**: Save time by creating templates for recurring routes
3. **Track Costs**: Record all costs for accurate ROI analysis
4. **Monitor Analytics**: Review weekly to identify improvement opportunities
5. **Update Regularly**: Keep driver certifications and vehicle maintenance current
6. **API Integration**: Use API for automation and external integrations

---

## 📚 Documentation

- **Full Documentation**: `routes/README_ENHANCEMENTS.md`
- **Implementation Summary**: `routes/IMPLEMENTATION_SUMMARY.md`
- **Enhancement Plan**: `.agent/workflows/route-optimizer-enhancement.md`

---

## 🆘 Troubleshooting

### Traffic data not loading?
- Check API keys in `.env`
- Verify internet connection
- Check API quota limits

### Charts not showing?
- Clear browser cache
- Check JavaScript console for errors
- Ensure Chart.js is loading

### API not working?
- Verify API key
- Check CORS settings
- Review error logs

### Database errors?
- Re-run `install_enhancements.php`
- Check database permissions
- Verify table creation

---

## 🎓 Learn More

### Video Tutorials (Coming Soon)
- Setting up drivers and vehicles
- Creating optimized routes
- Using the analytics dashboard
- API integration examples

### Sample Data
Want to test with sample data? Run:
```bash
php routes/seed_sample_data.php
```

---

## 🤝 Support

Need help?
1. Check documentation
2. Review error logs
3. Test with sample data
4. Contact administrator

---

## 🎉 You're Ready!

Your route optimizer is now equipped with:
- ✅ Real-time traffic integration
- ✅ Comprehensive analytics
- ✅ Cost optimization
- ✅ Driver & vehicle management
- ✅ REST API
- ✅ Route templates

**Start optimizing your routes today! 🚀**

---

**Quick Links**:
- [Analytics Dashboard](http://localhost/stock_management/routes/analytics.php)
- [Route Optimizer](http://localhost/stock_management/routes/index.php)
- [API Documentation](routes/README_ENHANCEMENTS.md#api-documentation)
