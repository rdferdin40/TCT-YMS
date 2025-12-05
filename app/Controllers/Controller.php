<?php
/**
 * TCT-YMS Base Controller
 *
 * All controllers should extend this class.
 */

namespace App\Controllers;

abstract class Controller
{
    /**
     * Current authenticated user
     */
    protected ?array $user;

    /**
     * Layout template
     */
    protected string $layout = 'layouts.app';

    /**
     * Constructor - initialize common controller properties
     */
    public function __construct()
    {
        $this->user = auth();
    }

    /**
     * Render view with layout
     */
    protected function view(string $view, array $data = []): string
    {
        // Add common data
        $data['user'] = $this->user;
        $data['_view'] = $view;

        // Render view content
        $content = view($view, $data);

        // If layout is set, wrap content
        if ($this->layout) {
            $data['content'] = $content;
            return view($this->layout, $data);
        }

        return $content;
    }

    /**
     * Render partial without layout
     */
    protected function partial(string $view, array $data = []): string
    {
        $data['user'] = $this->user;
        return view($view, $data);
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $status = 200): never
    {
        jsonResponse($data, $status);
    }

    /**
     * Return success JSON response
     */
    protected function success(mixed $data = null, string $message = 'Success'): never
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Return error JSON response
     */
    protected function error(string $message, int $status = 400, array $errors = []): never
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url, int $status = 302): never
    {
        redirect($url, $status);
    }

    /**
     * Redirect with flash message
     */
    protected function redirectWith(string $url, string $type, string $message): never
    {
        flash($type, $message);
        redirect($url);
    }

    /**
     * Redirect back with flash message
     */
    protected function back(): never
    {
        back();
    }

    /**
     * Redirect back with error
     */
    protected function backWithError(string $message, array $input = []): never
    {
        flash('error', $message);
        $_SESSION['_old_input'] = $input;
        back();
    }

    /**
     * Redirect back with success
     */
    protected function backWithSuccess(string $message): never
    {
        flash('success', $message);
        back();
    }

    /**
     * Validate request data
     */
    protected function validate(array $rules): array
    {
        $errors = [];
        $data = [];

        foreach ($rules as $field => $rule) {
            $value = input($field);
            $ruleList = is_array($rule) ? $rule : explode('|', $rule);

            foreach ($ruleList as $r) {
                $params = [];
                if (str_contains($r, ':')) {
                    [$r, $paramStr] = explode(':', $r, 2);
                    $params = explode(',', $paramStr);
                }

                $error = $this->validateRule($field, $value, $r, $params);
                if ($error) {
                    $errors[$field] = $error;
                    break;
                }
            }

            $data[$field] = $value;
        }

        if (!empty($errors)) {
            if (isAjax()) {
                $this->error('Validation failed', 422, $errors);
            }

            flash('errors', $errors);
            $_SESSION['_old_input'] = $data;
            back();
        }

        return $data;
    }

    /**
     * Validate single rule
     */
    private function validateRule(string $field, mixed $value, string $rule, array $params = []): ?string
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required' => empty($value) && $value !== '0' ? "{$label} is required" : null,
            'email' => $value && !isValidEmail($value) ? "{$label} must be a valid email" : null,
            'numeric' => $value && !is_numeric($value) ? "{$label} must be numeric" : null,
            'integer' => $value && !filter_var($value, FILTER_VALIDATE_INT) ? "{$label} must be an integer" : null,
            'min' => strlen($value) < ($params[0] ?? 0) ? "{$label} must be at least {$params[0]} characters" : null,
            'max' => strlen($value) > ($params[0] ?? 0) ? "{$label} must be at most {$params[0]} characters" : null,
            'in' => $value && !in_array($value, $params) ? "{$label} must be one of: " . implode(', ', $params) : null,
            'unique' => $this->validateUnique($field, $value, $params) ? "{$label} already exists" : null,
            'confirmed' => $value !== input($field . '_confirmation') ? "{$label} confirmation does not match" : null,
            'date' => $value && !strtotime($value) ? "{$label} must be a valid date" : null,
            'alpha' => $value && !ctype_alpha($value) ? "{$label} must contain only letters" : null,
            'alphanumeric' => $value && !ctype_alnum($value) ? "{$label} must contain only letters and numbers" : null,
            'regex' => $value && !preg_match($params[0] ?? '/.*/', $value) ? "{$label} format is invalid" : null,
            default => null,
        };
    }

    /**
     * Validate uniqueness
     */
    private function validateUnique(string $field, mixed $value, array $params): bool
    {
        if (!$value) {
            return false;
        }

        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;

        if (!$table) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $sqlParams = [$value];

        if ($exceptId) {
            $sql .= " AND id != ?";
            $sqlParams[] = $exceptId;
        }

        return \App\Database::fetchColumn($sql, $sqlParams) > 0;
    }

    /**
     * Authorize action or abort
     */
    protected function authorize(string $permission): void
    {
        if (!can($permission)) {
            abort(403, 'You do not have permission to perform this action');
        }
    }

    /**
     * Check if user has role
     */
    protected function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            return hasRole($roles);
        }
        return hasAnyRole($roles);
    }

    /**
     * Require specific role or abort
     */
    protected function requireRole(string|array $roles): void
    {
        if (!$this->hasRole($roles)) {
            abort(403, 'You do not have the required role');
        }
    }

    /**
     * Get uploaded file
     */
    protected function getUploadedFile(string $field): ?array
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $file = $_FILES[$field];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        return [
            'name' => $file['name'],
            'type' => $file['type'],
            'tmp_name' => $file['tmp_name'],
            'size' => $file['size'],
            'extension' => getExtension($file['name']),
        ];
    }

    /**
     * Store uploaded file
     */
    protected function storeUpload(string $field, string $directory = 'uploads'): ?string
    {
        $file = $this->getUploadedFile($field);

        if (!$file) {
            return null;
        }

        // Validate extension
        $allowed = config('app.upload.allowed_extensions', []);
        if (!in_array($file['extension'], $allowed)) {
            $this->backWithError('Invalid file type');
        }

        // Validate size
        $maxSize = config('app.upload.max_size', 10485760);
        if ($file['size'] > $maxSize) {
            $this->backWithError('File is too large');
        }

        // Generate unique filename
        $filename = randomString(16) . '.' . $file['extension'];
        $path = public_path($directory . '/' . date('Y/m'));

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $fullPath = $path . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            $this->backWithError('Failed to upload file');
        }

        return $directory . '/' . date('Y/m') . '/' . $filename;
    }

    /**
     * Export data as CSV
     */
    protected function exportCsv(array $data, string $filename, array $headers = []): never
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Write headers
        if (!empty($headers)) {
            fputcsv($output, $headers);
        } elseif (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }

        // Write data
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }

        fclose($output);
        exit;
    }
}
