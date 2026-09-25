using InventoryApp.Models;

namespace InventoryApp.Services
{
    public interface IInventoryService
    {
        List<InventoryItem> GetAllItems();
        void AddItem(InventoryItem item);
        void RestockItem(int id, int amount = 5);
        void DecreaseStock(int id, int amount = 1);
        decimal GetTotalInventoryValue();
        int GetLowStockCount();
        int GetOutOfStockCount();
    }

    public class InventoryService : IInventoryService
    {
        private readonly List<InventoryItem> _items = new();
        private int _nextId = 1;

        public InventoryService()
        {
            SeedInitialData();
        }

        private void SeedInitialData()
        {
            AddItem(new InventoryItem { Name = "Wireless Optical Mouse", Category = "Electronics", Sku = "ELE-001", UnitPrice = 45.00m, StockQuantity = 25, ReorderThreshold = 10 });
            AddItem(new InventoryItem { Name = "Mechanical Keyboard", Category = "Electronics", Sku = "ELE-002", UnitPrice = 180.00m, StockQuantity = 4, ReorderThreshold = 8 });
            AddItem(new InventoryItem { Name = "Ergonomic Desk Chair", Category = "Furniture", Sku = "FUR-001", UnitPrice = 350.00m, StockQuantity = 12, ReorderThreshold = 5 });
            AddItem(new InventoryItem { Name = "USB-C Multiport Hub", Category = "Electronics", Sku = "ELE-003", UnitPrice = 85.00m, StockQuantity = 0, ReorderThreshold = 6 });
            AddItem(new InventoryItem { Name = "Hardcover Notebook", Category = "Stationery", Sku = "STA-001", UnitPrice = 15.50m, StockQuantity = 40, ReorderThreshold = 15 });
        }

        public List<InventoryItem> GetAllItems() => _items.OrderBy(i => i.Category).ThenBy(i => i.Name).ToList();

        public void AddItem(InventoryItem item)
        {
            item.Id = _nextId++;
            if (string.IsNullOrWhiteSpace(item.Sku))
            {
                string catCode = item.Category.Length >= 3 ? item.Category.Substring(0, 3).ToUpper() : "GEN";
                item.Sku = $"{catCode}-{item.Id:D3}";
            }
            _items.Add(item);
        }

        public void RestockItem(int id, int amount = 5)
        {
            var item = _items.FirstOrDefault(i => i.Id == id);
            if (item != null) item.StockQuantity += amount;
        }

        public void DecreaseStock(int id, int amount = 1)
        {
            var item = _items.FirstOrDefault(i => i.Id == id);
            if (item != null && item.StockQuantity >= amount)
            {
                item.StockQuantity -= amount;
            }
        }

        public decimal GetTotalInventoryValue() => _items.Sum(i => i.TotalValue);
        public int GetLowStockCount() => _items.Count(i => i.IsLowStock);
        public int GetOutOfStockCount() => _items.Count(i => i.IsOutOfStock);
    }
}