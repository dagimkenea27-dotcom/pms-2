<?php
/**
 * Route Template Model
 * Manages reusable route templates for common delivery patterns
 */

require_once __DIR__ . '/../config/database.php';

class RouteTemplate {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Create a new route template
     */
    public function createTemplate($data) {
        try {
            $sql = "INSERT INTO route_templates (
                name, description, created_by, is_public, category,
                warehouse_locations, typical_stop_count, typical_distance_km,
                typical_duration_minutes, days_of_week, frequency
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['created_by'],
                $data['is_public'] ?? false,
                $data['category'] ?? null,
                json_encode($data['warehouse_locations'] ?? []),
                $data['typical_stop_count'] ?? 0,
                $data['typical_distance_km'] ?? 0,
                $data['typical_duration_minutes'] ?? 0,
                json_encode($data['days_of_week'] ?? []),
                $data['frequency'] ?? 'custom'
            ]);
            
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Create template error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add stops to a template
     */
    public function addTemplateStops($templateId, $stops) {
        try {
            $sql = "INSERT INTO route_template_stops (
                template_id, address, latitude, longitude, sequence_number,
                typical_time_window_start, typical_time_window_end, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            
            foreach ($stops as $index => $stop) {
                $stmt->execute([
                    $templateId,
                    $stop['address'],
                    $stop['latitude'] ?? null,
                    $stop['longitude'] ?? null,
                    $stop['sequence_number'] ?? $index + 1,
                    $stop['typical_time_window_start'] ?? null,
                    $stop['typical_time_window_end'] ?? null,
                    $stop['notes'] ?? null
                ]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Add template stops error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get template by ID with stops
     */
    public function getTemplate($templateId) {
        try {
            // Get template
            $sql = "SELECT * FROM route_templates WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                return null;
            }
            
            // Decode JSON fields
            $template['warehouse_locations'] = json_decode($template['warehouse_locations'], true);
            $template['days_of_week'] = json_decode($template['days_of_week'], true);
            
            // Get stops
            $sql = "SELECT * FROM route_template_stops 
                    WHERE template_id = ? 
                    ORDER BY sequence_number";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$templateId]);
            $template['stops'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $template;
        } catch (PDOException $e) {
            error_log("Get template error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all templates for a user
     */
    public function getUserTemplates($userId, $includePublic = true) {
        try {
            if ($includePublic) {
                $sql = "SELECT * FROM route_templates 
                        WHERE created_by = ? OR is_public = 1
                        ORDER BY use_count DESC, name ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId]);
            } else {
                $sql = "SELECT * FROM route_templates 
                        WHERE created_by = ?
                        ORDER BY use_count DESC, name ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId]);
            }
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields
            foreach ($templates as &$template) {
                $template['warehouse_locations'] = json_decode($template['warehouse_locations'], true);
                $template['days_of_week'] = json_decode($template['days_of_week'], true);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Get user templates error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get templates by category
     */
    public function getTemplatesByCategory($category, $userId = null) {
        try {
            if ($userId) {
                $sql = "SELECT * FROM route_templates 
                        WHERE category = ? AND (created_by = ? OR is_public = 1)
                        ORDER BY use_count DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$category, $userId]);
            } else {
                $sql = "SELECT * FROM route_templates 
                        WHERE category = ? AND is_public = 1
                        ORDER BY use_count DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$category]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get templates by category error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Apply template to create a new route
     */
    public function applyTemplate($templateId, $routeName, $userId) {
        try {
            $template = $this->getTemplate($templateId);
            if (!$template) {
                return false;
            }
            
            // Create route from template
            require_once __DIR__ . '/Route.php';
            $routeModel = new Route();
            
            $routeData = [
                'name' => $routeName,
                'warehouse_locations' => $template['warehouse_locations'],
                'driver_count' => 1, // Default, can be adjusted
                'country_code' => 'et', // Default, can be adjusted
                'created_by' => $userId,
                'stops' => []
            ];
            
            // Convert template stops to route stops format
            foreach ($template['stops'] as $stop) {
                $routeData['stops'][] = [
                    'address' => $stop['address'],
                    'lat' => $stop['latitude'],
                    'lon' => $stop['longitude'],
                    'display_name' => $stop['address']
                ];
            }
            
            $routeId = $routeModel->saveRoute(
                $routeData['name'],
                $routeData['warehouse_locations'],
                $routeData['driver_count'],
                $routeData['country_code'],
                $routeData['created_by'],
                [$routeData['stops']] // Wrap in array for single driver
            );
            
            if ($routeId) {
                // Increment template use count
                $this->incrementUseCount($templateId);
                return $routeId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Apply template error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update template
     */
    public function updateTemplate($templateId, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'name', 'description', 'is_public', 'category',
                'warehouse_locations', 'typical_stop_count', 'typical_distance_km',
                'typical_duration_minutes', 'days_of_week', 'frequency'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    
                    // JSON encode certain fields
                    if (in_array($field, ['warehouse_locations', 'days_of_week'])) {
                        $values[] = json_encode($data[$field]);
                    } else {
                        $values[] = $data[$field];
                    }
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $values[] = $templateId;
            $sql = "UPDATE route_templates SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Update template error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete template
     */
    public function deleteTemplate($templateId, $userId) {
        try {
            // Check ownership
            $sql = "SELECT created_by FROM route_templates WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$templateId]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template || $template['created_by'] != $userId) {
                return false;
            }
            
            // Delete template and stops (cascade)
            $sql = "DELETE FROM route_templates WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$templateId]);
        } catch (PDOException $e) {
            error_log("Delete template error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Increment template use count
     */
    public function incrementUseCount($templateId) {
        try {
            $sql = "UPDATE route_templates 
                    SET use_count = use_count + 1, last_used_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$templateId]);
        } catch (PDOException $e) {
            error_log("Increment use count error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get popular templates
     */
    public function getPopularTemplates($limit = 10) {
        try {
            $sql = "SELECT * FROM route_templates 
                    WHERE is_public = 1
                    ORDER BY use_count DESC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit]);
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($templates as &$template) {
                $template['warehouse_locations'] = json_decode($template['warehouse_locations'], true);
                $template['days_of_week'] = json_decode($template['days_of_week'], true);
            }
            
            return $templates;
        } catch (PDOException $e) {
            error_log("Get popular templates error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search templates
     */
    public function searchTemplates($query, $userId = null) {
        try {
            $searchTerm = "%$query%";
            
            if ($userId) {
                $sql = "SELECT * FROM route_templates 
                        WHERE (name LIKE ? OR description LIKE ? OR category LIKE ?)
                        AND (created_by = ? OR is_public = 1)
                        ORDER BY use_count DESC
                        LIMIT 20";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $userId]);
            } else {
                $sql = "SELECT * FROM route_templates 
                        WHERE (name LIKE ? OR description LIKE ? OR category LIKE ?)
                        AND is_public = 1
                        ORDER BY use_count DESC
                        LIMIT 20";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search templates error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get templates for a specific day of week
     */
    public function getTemplatesForDay($dayOfWeek, $userId = null) {
        try {
            if ($userId) {
                $sql = "SELECT * FROM route_templates 
                        WHERE JSON_CONTAINS(days_of_week, ?)
                        AND (created_by = ? OR is_public = 1)
                        ORDER BY use_count DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([json_encode($dayOfWeek), $userId]);
            } else {
                $sql = "SELECT * FROM route_templates 
                        WHERE JSON_CONTAINS(days_of_week, ?)
                        AND is_public = 1
                        ORDER BY use_count DESC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([json_encode($dayOfWeek)]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get templates for day error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clone a template
     */
    public function cloneTemplate($templateId, $newName, $userId) {
        try {
            $template = $this->getTemplate($templateId);
            if (!$template) {
                return false;
            }
            
            // Create new template
            $newTemplateId = $this->createTemplate([
                'name' => $newName,
                'description' => $template['description'],
                'created_by' => $userId,
                'is_public' => false, // Clones are private by default
                'category' => $template['category'],
                'warehouse_locations' => $template['warehouse_locations'],
                'typical_stop_count' => $template['typical_stop_count'],
                'typical_distance_km' => $template['typical_distance_km'],
                'typical_duration_minutes' => $template['typical_duration_minutes'],
                'days_of_week' => $template['days_of_week'],
                'frequency' => $template['frequency']
            ]);
            
            if ($newTemplateId) {
                // Copy stops
                $this->addTemplateStops($newTemplateId, $template['stops']);
                return $newTemplateId;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Clone template error: " . $e->getMessage());
            return false;
        }
    }
}
?>
