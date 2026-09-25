using InventoryApp.Services;

var builder = WebApplication.CreateBuilder(args);

// Register Razor Pages and Inventory Service
builder.Services.AddRazorPages();
builder.Services.AddSingleton<IInventoryService, InventoryService>();

var app = builder.Build();

if (!app.Environment.IsDevelopment())
{
    app.UseExceptionHandler("/Error");
    app.UseHsts();
}

app.UseHttpsRedirection();
app.UseStaticFiles();
app.UseRouting();
app.UseAuthorization();
app.MapRazorPages();

app.Run();