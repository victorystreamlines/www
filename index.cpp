// Simple program that stays open until you press Enter
#include <iostream>
#include <limits>

int main(){
    std::cout << "Hello World" << std::endl;
    std::cout << "\nPress Enter to exit...";
    // Flush output so text appears before waiting
    std::cout.flush();
    // Wait for a single Enter (works when double‑clicking the .exe)
    std::cin.get();
    return 0;
}
