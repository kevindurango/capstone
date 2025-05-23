# Test script for forgot-password endpoint

$header = @{
    "Content-Type" = "application/json"
}

$body = @{
    email = "test@example.com"
} | ConvertTo-Json

Write-Host "Testing forgot-password.php endpoint..."
Write-Host "Sending request with payload: $body"

try {
    $response = Invoke-WebRequest -Uri "http://localhost/capstone/my-new-app/api/forgot-password.php" `
                                 -Method Post `
                                 -Headers $header `
                                 -Body $body
    
    Write-Host "`nResponse Status: $($response.StatusCode)"
    Write-Host "`nResponse Headers:"
    $response.Headers | Format-Table -AutoSize
    
    Write-Host "`nResponse Content:"
    $response.Content
    
    try {
        $jsonResponse = $response.Content | ConvertFrom-Json
        Write-Host "`nParsed JSON Response:"
        $jsonResponse | Format-List
    } catch {
        Write-Host "`nFailed to parse response as JSON: $_"
    }
    
} catch {
    Write-Host "`nError occurred: $_"
    Write-Host "Status code: $($_.Exception.Response.StatusCode)"
}
