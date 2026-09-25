using System.ComponentModel.DataAnnotations;

namespace InventoryApp.Models
{
    public class InventoryItem
    {
        public int Id { get; set; }

        [Required(ErrorMessage = "Item name is required")]
        [StringLength(60, MinimumLength = 2)]
        public string Name { get; set; } = string.Empty;

        [Required(ErrorMessage = "Category is required")]
        public string Category { get; set; } = string.Empty;

        public string Sku { get; set; } = string.Empty;

        [Range(0.1, 1000.0, ErrorMessage = "Price must be between RM 0.10 and RM 1000.00")]
        public decimal UnitPrice { get; set; }

        [Range(0, 5000, ErrorMessage = "Stock must be 0 or positive")]
        public int StockQuantity { get; set; }

        public int ReorderThreshold { get; set; } = 10;

        // Computed Properties
        public bool IsLowStock => StockQuantity > 0 && StockQuantity <= ReorderThreshold;
        public bool IsOutOfStock => StockQuantity == 0;
        public decimal TotalValue => UnitPrice * StockQuantity;
    }
}