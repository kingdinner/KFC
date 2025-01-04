<?php

namespace App\Http\Controllers\API\LandingPage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FAQ;
use App\Models\HRRule;
use Maatwebsite\Excel\Facades\Excel;

class HRFAQController extends Controller
{
    public function landingPage()
    {
        $hrRules = HRRule::all();
        $faqs = FAQ::all();

        return response()->json([
            'hr_rules' => $hrRules,
            'faqs' => $faqs
        ]);
    }

    public function storeOrUpdateFAQ(Request $request)
    {
        return $request->hasFile('file') 
            ? $this->handleFAQFileUpload($request) 
            : $this->handleManualFAQInsert($request);
    }

    public function storeOrUpdateHRRule(Request $request)
    {
        return $request->hasFile('file') 
            ? $this->handleHRRuleFileUpload($request) 
            : $this->handleManualHRRuleInsert($request);
    }

    private function handleFAQFileUpload(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,xlsx,xls']);
        $faqs = $this->parseFile($request->file('file'));

        $savedFAQs = array_filter(array_map(function ($faqData) {
            if (!empty($faqData['question']) && !empty($faqData['answer'])) {
                $faq = FAQ::firstOrNew(['question' => $faqData['question']]);
                $faq->answer = $faqData['answer'];
                $faq->save();
                return $faq;
            }
            return null;
        }, $faqs));

        return response()->json([
            'message' => 'FAQs uploaded and saved successfully',
            'faqs' => array_values($savedFAQs)
        ]);
    }

    private function handleManualFAQInsert(Request $request)
    {
        $validatedData = $request->validate([
            'faqs' => 'required|array',
            'faqs.*.question' => 'required|string|max:255',
            'faqs.*.answer' => 'required|string',
        ]);

        $savedFAQs = array_map(function ($faqData) {
            $faq = FAQ::firstOrNew(['question' => $faqData['question']]);
            $faq->fill($faqData)->save();
            return $faq;
        }, $validatedData['faqs']);

        return response()->json([
            'message' => 'FAQs saved successfully',
            'faqs' => $savedFAQs
        ]);
    }

    private function handleHRRuleFileUpload(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,xlsx,xls']);
        $hrRules = $this->parseFile($request->file('file'));

        $savedHRRules = array_filter(array_map(function ($ruleData) {
            if (!empty($ruleData['rule'])) {
                $hrRule = HRRule::firstOrNew();
                $hrRule->rule = $ruleData['rule'];
                $hrRule->save();
                return $hrRule;
            }
            return null;
        }, $hrRules));

        return response()->json([
            'message' => 'HR Rules uploaded and saved successfully',
            'hr_rules' => array_values($savedHRRules)
        ]);
    }

    private function handleManualHRRuleInsert(Request $request)
    {
        $validatedData = $request->validate(['rule' => 'required|string|max:255']);
        
        $hrRule = HRRule::firstOrNew();
        $hrRule->fill($validatedData)->save();

        return response()->json([
            'message' => 'HR Rule saved successfully',
            'hr_rule' => $hrRule
        ]);
    }

    private function parseFile($file)
    {
        return Excel::toArray([], $file->getRealPath())[0] ?? []; // Reads the first sheet into an array
    }
}